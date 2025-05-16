<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    "ID", "Nombre", "Marca", "Presentación Comercial", "Registro Sanitario",
    "Clasificación Riesgo", "Vida Útil", "Fecha Vencimiento", "Lote",
    "Fecha Creación", "Creado Por"
];

// Estilos bonitos para los encabezados
$col = 'A';
foreach ($headers as $header) {
    $cell = $col . '1';
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD9D9D9'); // gris claro
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $col++;
}

// Consultar datos de la tabla
$sql = "SELECT * FROM reactivo_vigilancia";
$stmt = $pdo->query($sql);

$rowNum = 2;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $col = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue($col . $rowNum, $value);
        $col++;
    }
    $rowNum++;
}

// Auto ajustar columnas
$maxCol = chr(ord('A') + count($headers) - 1);
for ($c = 'A'; $c <= $maxCol; $c++) {
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

// Encabezados para forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reactivos_vigilancia.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
