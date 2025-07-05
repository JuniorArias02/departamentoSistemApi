<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';

header("Access-Control-Allow-Origin: https://departamento-sistemasips.vercel.app");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados completos sin ID
$headers = [
    "Código",
    "Código de Barras",
    "Nombre",
    "Grupo",
    "Vida Útil",
    "Vida Útil NIIF",
    "Dependencia",
    "Responsable",
    "Centro de Costo",
    "Ubicación",
    "Proveedor",
    "Fecha Compra",
    "Soporte",
    "Descripción",
    "Estado",
    "Marca",
    "Modelo",
    "Serial",
    "Escritura",
    "Matrícula",
    "Valor Compra",
    "Salvamento",
    "Depreciación",
    "Depreciación NIIF",
    "Meses Depreciación",
    "Meses Dep. NIIF",
    "Tipo Adquisición",
    "Fecha Calibrado",
    "Sede"
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

// Consulta completa del inventario (excepto ID)
$sql = "SELECT 
  i.codigo, i.codigo_barras, i.nombre, i.grupo, i.vida_util, i.vida_util_niff,
  i.dependencia, i.responsable, i.centro_costo, i.ubicacion, i.proveedor, i.fecha_compra,
  i.soporte, i.descripcion, i.estado, i.marca, i.modelo, i.serial,
  i.escritura, i.matricula, i.valor_compra, i.salvamenta, i.depreciacion,
  i.depreciacion_niif, i.meses, i.meses_niif, i.tipo_adquisicion, i.calibrado,
  s.nombre AS sede_nombre
FROM inventario i
LEFT JOIN sedes s ON i.sede_id = s.id";

$stmt = $pdo->query($sql);

// Función para formatear fechas
function formatDate($date)
{
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

// Función para formatear valores monetarios
function formatCurrency($value)
{
    if ($value === null) return '';
    return number_format($value, 2, ',', '.');
}

$rowNum = 2;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $col = 'A';

    $sheet->setCellValue($col++ . $rowNum, $row['codigo']);
    $sheet->setCellValue($col++ . $rowNum, $row['codigo_barras']);
    $sheet->setCellValue($col++ . $rowNum, $row['nombre']);
    $sheet->setCellValue($col++ . $rowNum, $row['grupo']);
    $sheet->setCellValue($col++ . $rowNum, formatDate($row['vida_util']));
    $sheet->setCellValue($col++ . $rowNum, formatDate($row['vida_util_niff']));
    $sheet->setCellValue($col++ . $rowNum, $row['dependencia']);
    $sheet->setCellValue($col++ . $rowNum, $row['responsable']);
    $sheet->setCellValue($col++ . $rowNum, $row['centro_costo']);
    $sheet->setCellValue($col++ . $rowNum, $row['ubicacion']);
    $sheet->setCellValue($col++ . $rowNum, $row['proveedor']);
    $sheet->setCellValue($col++ . $rowNum, formatDate($row['fecha_compra']));
    $sheet->setCellValue($col++ . $rowNum, $row['soporte']);
    $sheet->setCellValue($col++ . $rowNum, $row['descripcion']);
    $sheet->setCellValue($col++ . $rowNum, $row['estado']);
    $sheet->setCellValue($col++ . $rowNum, $row['marca']);
    $sheet->setCellValue($col++ . $rowNum, $row['modelo']);
    $sheet->setCellValue($col++ . $rowNum, $row['serial']);
    $sheet->setCellValue($col++ . $rowNum, $row['escritura']);
    $sheet->setCellValue($col++ . $rowNum, $row['matricula']);
    $sheet->setCellValue($col++ . $rowNum, formatCurrency($row['valor_compra']));
    $sheet->setCellValue($col++ . $rowNum, $row['salvamenta']);
    $sheet->setCellValue($col++ . $rowNum, formatCurrency($row['depreciacion']));
    $sheet->setCellValue($col++ . $rowNum, formatCurrency($row['depreciacion_niif']));
    $sheet->setCellValue($col++ . $rowNum, $row['meses']);
    $sheet->setCellValue($col++ . $rowNum, $row['meses_niif']);
    $sheet->setCellValue($col++ . $rowNum, $row['tipo_adquisicion']);
    $sheet->setCellValue($col++ . $rowNum, formatDate($row['calibrado']));
    $sheet->setCellValue($col++ . $rowNum, $row['sede_nombre']);

    // Información de creación
    $rowNum++;
}

// Aplicar formatos especiales a columnas
$sheet->getStyle('K2:K' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$sheet->getStyle('L2:L' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$sheet->getStyle('P2:P' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$sheet->getStyle('V2:X' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('AC2:AC' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$sheet->getStyle('AF2:AF' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

// Auto ajustar columnas


$colCount = count($headers);
$lastColLetter = Coordinate::stringFromColumnIndex($colCount); // ← última letra válida

// Autoajustar ancho de columnas
for ($i = 1; $i <= $colCount; $i++) {
    $colLetter = Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// Configurar hoja para mejor visualización
$sheet->setAutoFilter("A1:{$lastColLetter}1");
$sheet->freezePane('A2');

// Forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="inventario_completo.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

