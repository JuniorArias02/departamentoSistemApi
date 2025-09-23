<?php
require_once '../vendor/autoload.php';
require_once '../middlewares/cors.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- Cargar plantilla ---
$templatePath = __DIR__ . "/../public/plantilla_descuento_fijos.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// --- ID del descuento ---
$idEntrega = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idEntrega <= 0) {
	die("ID de descuento inválido");
}

$sql = "SELECT 
    d.id,
    d.entrega_fijos_id,
    d.consecutivo,
    d.fecha_solicitud,
    d.trabajador_id,
    p.nombre   AS trabajador_nombre,
    p.cedula   AS trabajador_cedula,
    p.telefono AS trabajador_telefono,
    d.tipo_contrato,
    d.firma_trabajador,
    d.motivo_solicitud,
    d.valor_total_descontar,
    d.numero_cuotas,
    d.numero_cuotas_aprobadas,
    
    r.nombre AS responsable_nombre,
    f.nombre AS facturacion_nombre,
    g.nombre AS gestion_financiera_nombre,
    t.nombre AS talento_humano_nombre,

    d.firma_responsable_aprobacion,
    d.firma_jefe_inmediato,
    d.firma_facturacion,
    d.firma_gestion_financiera,
    d.firma_talento_humano,
    d.observaciones

FROM cp_solicitud_descuento AS d
LEFT JOIN personal AS p ON d.trabajador_id = p.id
LEFT JOIN personal AS r ON d.personal_responsable_aprobacion = r.id
LEFT JOIN personal AS f ON d.personal_facturacion = f.id
LEFT JOIN personal AS g ON d.personal_gestion_financiera = g.id
LEFT JOIN personal AS t ON d.personal_talento_humano = t.id
WHERE d.id = :id
ORDER BY d.fecha_solicitud DESC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $idEntrega]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// ======================= LLENAR HOJA =======================
if ($data) {
	$sheet->setCellValue("D5", $data['trabajador_nombre']);
	$sheet->setCellValue("C6", $data['trabajador_cedula']);
	$sheet->setCellValue("I6", $data['fecha_solicitud']);

	$tipo_contrato = $data['tipo_contrato'];
	switch ($tipo_contrato) {
		case "OPS":
			$sheet->setCellValue("G8", 'X');
			break;
		case "NOMINA":
			$sheet->setCellValue("E9", 'X');
			break;
	}



	$sheet->setCellValue("F14", $data['motivo_solicitud']);
	$sheet->setCellValue("F15", $data['valor_total_descontar']);
	$sheet->setCellValue("E20", $data['numero_cuotas']);
	$sheet->setCellValue("E22", $data['numero_cuotas_aprobadas']);
	$sheet->setCellValue("E23", $data['responsable_nombre']);
	$sheet->setCellValue("G28", $data['facturacion_nombre']);
	$sheet->setCellValue("G30", $data['gestion_financiera_nombre']);
	$sheet->setCellValue("G32", $data['talento_humano_nombre']);
	$sheet->setCellValue("A33", $data['observaciones']);

	insertarFirma($sheet, $data['firma_trabajador'], "A18", 60, 8);
	insertarFirma($sheet, $data['firma_responsable_aprobacion'], "E24", 8, 8);
	insertarFirma($sheet, $data['firma_jefe_inmediato'], "H18", 20, 8);
	insertarFirma($sheet, $data['firma_facturacion'], "G27", 8, 8);
	insertarFirma($sheet, $data['firma_gestion_financiera'], "G29", 8, 8);
	insertarFirma($sheet, $data['firma_talento_humano'], "G31", 8, 8);
}

// --- Descargar Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="descuento_fijos.xlsx"');
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

	$drawing->setOffsetX($offsetX);
	$drawing->setOffsetY($offsetY);

	$drawing->setWorksheet($sheet);
}
