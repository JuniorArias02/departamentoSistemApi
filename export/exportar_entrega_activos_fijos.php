<?php
require_once '../vendor/autoload.php';
require_once '../middlewares/cors.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// --- Cargar plantilla ---
$templatePath = __DIR__ . "/../public/plantilla_entrega_activos_fijos.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// --- ID de la entrega ---
$idEntrega = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idEntrega <= 0) {
	die("ID de entrega inválido");
}
// ======================= CONSULTA PRINCIPAL =======================
$sqlEntrega = "SELECT 
    ef.fecha_entrega,
    ef.firma_quien_entrega,
    ef.firma_quien_recibe,
    s.nombre AS sede,
    p.nombre AS personal,
    p.cedula,
    c.nombre AS cargo,
    dep.nombre AS dependencia,
    coord.nombre AS coordinador

FROM cp_entrega_activos_fijos ef
LEFT JOIN personal p ON p.id = ef.personal_id
LEFT JOIN sedes s ON s.id = ef.sede_id
LEFT JOIN p_cargo c ON c.id = p.cargo_id
LEFT JOIN dependencias_sedes dep ON dep.id = ef.proceso_solicitante

LEFT JOIN personal coord ON coord.id = ef.coordinador_id

WHERE ef.id = :id;
";

$stmt = $pdo->prepare($sqlEntrega);
$stmt->execute([':id' => $idEntrega]);
$entrega = $stmt->fetch(PDO::FETCH_ASSOC);

// ======================= CONSULTA ITEMS =======================
$sqlItems = "SELECT 
    efi.es_accesorio,
    efi.accesorio_descripcion,
    i.nombre,
    i.marca,
    i.serial,
    i.codigo,
    i.proveedor,
    i.soporte,
    i.observaciones,
	i.estado
FROM cp_entrega_activos_fijos_items efi
LEFT JOIN inventario i ON i.id = efi.item_id
LEFT JOIN personal coord ON coord.id = i.coordinador_id
WHERE efi.entrega_activos_id = :id";

$stmt2 = $pdo->prepare($sqlItems);
$stmt2->execute([':id' => $idEntrega]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ======================= LLENAR HOJA =======================
// 👉 Datos de la entrega (cabecera)
if ($entrega) {
	$fecha = new DateTime($entrega['fecha_entrega']);

	$sheet->setCellValue("B8", $fecha->format("d"));
	$sheet->setCellValue("C8", $fecha->format("m"));
	$sheet->setCellValue("D8", $fecha->format("Y"));
	$sheet->setCellValue("O6", $entrega['coordinador']);
	$sheet->setCellValue("O8", $entrega['sede']);
	$sheet->setCellValue("H6", $entrega['personal']);
	$sheet->setCellValue("H7", $entrega['cedula']);
	$sheet->setCellValue("H8", $entrega['cargo']);
	$sheet->setCellValue("O7", $entrega['dependencia']);

	// firmas
	insertarFirma($sheet, $entrega['firma_quien_entrega'], "H20", 5, 10);
	insertarFirma($sheet, $entrega['firma_quien_recibe'], "S20", -10, 10);
}

// 👉 Items (detalle, como tabla)
$startRow = 14;
foreach ($items as $i => $item) {
	$row = $startRow + $i;

	$sheet->setCellValue("B{$row}", $item['nombre']);
	$sheet->setCellValue("E{$row}", $item['proveedor']);
	$sheet->setCellValue("G{$row}", $item['soporte']);
	$sheet->setCellValue("H{$row}", $item['marca']);
	$sheet->setCellValue("I{$row}", $item['serial']);
	$sheet->setCellValue("K{$row}", $item['codigo']);


	$prefijo = preg_replace('/[^A-Z]/i', '', $item['codigo']);

	switch ($prefijo) {
		case "EB":
			$sheet->setCellValue("L{$row}", "X");
			break;
		case "MAQ":
			$sheet->setCellValue("M{$row}", "X");
			break;
		case "ME":
			$sheet->setCellValue("N{$row}", "X");
			break;
		case "EC":
			$sheet->setCellValue("O{$row}", "X");
			break;
		case "MC":
			$sheet->setCellValue("P{$row}", "X");
			break;
		default:
			break;
	}

	if ($item['es_accesorio']) {
		$sheet->setCellValue("R{$row}", "X");
	} else {
		$sheet->setCellValue("S{$row}", "X");
	}

	$sheet->setCellValue("T{$row}", $item['accesorio_descripcion']);
}

// --- Descargar Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="entrega_activos_fijos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;

// ======================= FUNCION FIRMA =======================
function insertarFirma($sheet, $rutaFirma, $celda, $offsetX = 250, $offsetY = 15)
{
	if (empty($rutaFirma)) return;

	$fullPath = __DIR__ . "/../" . ltrim($rutaFirma, '/');
	if (!file_exists($fullPath)) {
		error_log("Firma no encontrada: " . $fullPath);
		return;
	}

	preg_match('/([A-Z]+)([0-9]+)/', $celda, $matches);
	$row = (int)$matches[2];

	$widthCm  = 5.5;
	$heightCm = 1.5;

	$widthPx  = $widthCm * 37.8;
	$heightPx = $heightCm * 37.8;

	$rowHeightPx = 75;
	$rowHeightPoints = $rowHeightPx / 1.33;
	$sheet->getRowDimension($row)->setRowHeight($rowHeightPoints);

	$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
	$drawing->setPath($fullPath);
	$drawing->setCoordinates($celda);

	$drawing->setResizeProportional(false);
	$drawing->setWidth($widthPx);
	$drawing->setHeight($heightPx);

	// ahora con parámetros
	$drawing->setOffsetX($offsetX);
	$drawing->setOffsetY($offsetY);

	$drawing->setWorksheet($sheet);
}
