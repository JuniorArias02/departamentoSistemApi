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
    "ID", "Código", "Nombre", "Dependencia", "Responsable",
    "Marca", "Modelo", "Serial", "Sede", "Creado Por", "Fecha Creación"
];

// Estilo para encabezados
$col = 'A';
foreach ($headers as $header) {
    $cell = $col . '1';
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD9D9D9');
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $col++;
}

// Consulta del inventario con el nombre de la sede
$sql = "SELECT i.id, i.codigo, i.nombre, i.dependencia, i.responsable,
               i.marca, i.modelo, i.serial, s.nombre AS sede,
               i.creado_por, i.fecha_creacion
        FROM inventario i
        LEFT JOIN sedes s ON i.sede_id = s.id";
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

// Forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="inventario.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
