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
    "ID", "Descripción", "Marca", "Serie", "Presentación Comercial",
    "Registro Sanitario", "Clasificación Riesgo", "Vida Útil",
    "Lote", "Fecha Vencimiento", "Fecha Creación", "Creado Por"
];

// Escribir encabezados y aplicar estilos
$column = 'A';
foreach ($headers as $header) {
    $cell = $column . '1';
    $sheet->setCellValue($cell, $header);
    // Negrita
    $sheet->getStyle($cell)->getFont()->setBold(true);
    // Fondo gris clarito
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD9D9D9');
    // Texto centrado
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Borde inferior
    $sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $column++;
}

// Obtener datos desde la base
$sql = "SELECT * FROM dispositivos_medicos";
$stmt = $pdo->query($sql);

$rowNumber = 2;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $column = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue($column . $rowNumber, $value);
        $column++;
    }
    $rowNumber++;
}

// Ajustar ancho de columnas automático
$maxCol = chr(ord('A') + count($headers) - 1);
for ($col = 'A'; $col <= $maxCol; $col++) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="dispositivos_medicos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
