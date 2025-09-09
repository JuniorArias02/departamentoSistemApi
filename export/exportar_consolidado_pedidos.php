<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Headers
header("Access-Control-Allow-Origin: https://departamento-sistemasips.vercel.app");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Recibir filtros
$data = json_decode(file_get_contents("php://input"), true);
$fechaInicio = $data['fecha_inicio'] ?? '2025-01-01';
$fechaFin = $data['fecha_fin'] ?? '2025-12-31';

// Consulta 
$sqlPedidos = "
SELECT 
    u.sede_id AS SEDE,
    p.consecutivo AS CONSECUTIVO,
	p.proceso_solicitante AS PROCESO,
	s.nombre AS SEDE,
    p.observacion AS DESCRIPCION,
    p.observacion_diligenciado AS OBSERVACION,
    ts.nombre AS TIPO_COMPRA,
    p.estado_compras AS APROBACION,
    p.fecha_solicitud AS FECHA_SOLICITUD,
    p.fecha_compra AS FECHA_RESPUESTA,
    p.responsable_aprobacion_firma AS FIRMA_RESPONSABLE,
    p.fecha_gerencia AS FECHA_RESPUESTA_SOLICITANTE
FROM 
    cp_pedidos p
LEFT JOIN 
    usuarios u ON u.id = p.elaborado_por
LEFT JOIN
    cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
LEFT JOIN
    sedes s ON s.id = p.sede_id
WHERE 
    p.fecha_solicitud BETWEEN :fecha_inicio AND :fecha_fin
ORDER BY p.fecha_solicitud ASC
";

$stmt = $pdo->prepare($sqlPedidos);
$stmt->execute([
    ':fecha_inicio' => $fechaInicio,
    ':fecha_fin' => $fechaFin
]);

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Cargar plantilla ---
$templatePath = __DIR__ . "/../public/plantilla_consolidadoPedidos.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// --- Poner los datos en la hoja ---
$startRow = 3;

foreach ($pedidos as $i => $pedido) {
    $row = $startRow + $i;
    $sheet->setCellValue("A{$row}", $pedido['FECHA_SOLICITUD']);
	$sheet->setCellValue("B{$row}", $pedido['PROCESO']);
	$sheet->setCellValue("C{$row}", $pedido['SEDE']);
    $sheet->setCellValue("D{$row}", $pedido['CONSECUTIVO']);
    $sheet->setCellValue("E{$row}", $pedido['DESCRIPCION']);
    $sheet->setCellValue("F{$row}", $pedido['OBSERVACION']);
    $sheet->setCellValue("G{$row}", $pedido['TIPO_COMPRA']);
    $sheet->setCellValue("H{$row}", $pedido['APROBACION']);
    $sheet->setCellValue("M{$row}", $pedido['FECHA_RESPUESTA']);
    $sheet->setCellValue("N{$row}", $pedido['FECHA_RESPUESTA_SOLICITANTE']);
    insertarFirma($sheet, $pedido['FIRMA_RESPONSABLE'], "K{$row}");
}

// --- Descargar Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="consolidado_pedidos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;

function insertarFirma($sheet, $rutaFirma, $celda)
{
    if (empty($rutaFirma)) return;

    $fullPath = __DIR__ . "/../" . $rutaFirma;
    if (!file_exists($fullPath)) return;

    preg_match('/([A-Z]+)([0-9]+)/', $celda, $matches);
    $col = $matches[1];
    $row = (int)$matches[2];

    $filaHeight = $sheet->getRowDimension($row)->getRowHeight();
    if ($filaHeight <= 0) $filaHeight = 60;

    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setPath($fullPath);
    $drawing->setCoordinates($celda);
    $drawing->setHeight($filaHeight);
    $drawing->setWorksheet($sheet);
}

