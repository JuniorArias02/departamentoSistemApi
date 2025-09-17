<?php
require_once '../vendor/autoload.php';
require_once '../middlewares/cors.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// --- Cargar plantilla ---
$templatePath = __DIR__ . "/../public/plantilla_control_entrega_solicitudes.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

$sql = "SELECT
              p.proceso_solicitante,
              p.consecutivo,
              p.observacion,
              COUNT(i.id) AS total_items,
              e.fecha       AS fecha_entrega,
              e.firma_quien_recibe,
              e.factura_proveedor
            FROM cp_entrega_solicitud e
            INNER JOIN cp_pedidos p
              ON p.consecutivo = e.consecutivo_id
            LEFT JOIN cp_items_pedidos i
              ON i.cp_pedido = p.id
            WHERE e.estado = 1
            GROUP BY
              p.id,
              p.proceso_solicitante,
              p.consecutivo,
              p.observacion,
              e.fecha,
              e.firma_quien_recibe,
              e.factura_proveedor";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Poner los datos en la hoja ---
$startRow = 6;
$count = 0;

foreach ($entregas as $i => $entrega) {
	$row = $startRow + $i;
	$sheet->setCellValue("B{$row}", $count + 1);
	$fecha = new DateTime($entrega['fecha_entrega']);
	$fechaExcel = Date::PHPToExcel($fecha);

	// primero asignas la fecha
	$sheet->setCellValue("C{$row}", $fechaExcel);

	// luego le das formato
	$sheet->getStyle("C{$row}")
		->getNumberFormat()
		->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);

	$sheet->setCellValue("D{$row}", $entrega['observacion']);
	$sheet->setCellValue("E{$row}", $entrega['total_items']);
	$sheet->setCellValue("F{$row}", $entrega['factura_proveedor']);
	$sheet->setCellValue("G{$row}", $entrega['consecutivo']);
	$sheet->setCellValue("H{$row}", $entrega['proceso_solicitante']);
	insertarFirma($sheet, $entrega['firma_quien_recibe'], "I{$row}");
	$count++;
}

// --- Descargar Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="control_entrega_solicitudes_pedidos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;

function insertarFirma($sheet, $rutaFirma, $celda)
{
	if (empty($rutaFirma)) return;

	$fullPath = __DIR__ . "/../" . ltrim($rutaFirma, '/');
	if (!file_exists($fullPath)) {
		error_log("Firma no encontrada: " . $fullPath);
		return;
	}

	preg_match('/([A-Z]+)([0-9]+)/', $celda, $matches);
	$row = (int)$matches[2];

	// Medidas exactas en cm
	$widthCm  = 5.5;
	$heightCm = 1.5;

	// Conversión cm → px (96 dpi)
	$widthPx  = $widthCm * 37.8;  // ≈ 170 px
	$heightPx = $heightCm * 37.8; // ≈ 87 px

	// Altura de fila fija (75 px → 56 pt aprox)
	$rowHeightPx = 75;
	$rowHeightPoints = $rowHeightPx / 1.33;
	$sheet->getRowDimension($row)->setRowHeight($rowHeightPoints);

	// Insertar la imagen con tamaño fijo
	$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
	$drawing->setPath($fullPath);
	$drawing->setCoordinates($celda);

	// Forzar ancho y alto (truco: usar ambos en orden y desactivar proporción)
	$drawing->setResizeProportional(false);
	$drawing->setWidth($widthPx);
	$drawing->setHeight($heightPx);

	// Offsets para centrar mejor dentro de la celda
	$drawing->setOffsetX(15);
	$drawing->setOffsetY(8);

	$drawing->setWorksheet($sheet);
}

