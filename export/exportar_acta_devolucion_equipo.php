<?php
require_once '../vendor/autoload.php';
require_once '../middlewares/cors.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// ======================= CARGAR PLANTILLA =======================
$templatePath = __DIR__ . "/../public/plantilla_devolucion_equipo.xlsx";
$spreadsheet  = IOFactory::load($templatePath);
$sheet        = $spreadsheet->getActiveSheet();

// ======================= ID ENTREGA =======================
$idEntrega = isset($_GET['id_entrega']) ? (int)$_GET['id_entrega'] : 0;
if ($idEntrega <= 0) {
	die("ID de entrega inválido");
}

// ======================= CONSULTA =======================
$sql = "
SELECT  
	pc_en.id AS ID_ENTREGA,
	pc_en.fecha_entrega AS FECHA_ENTREGA,

	pc_dev.fecha_devolucion AS FECHA_DEVOLUCION,
	pc_dev.firma_recibe AS FIRMA_RECIBE,
	pc_dev.firma_entrega AS FIRMA_ENTREGA,
	pc_dev.observaciones AS OBSERVACIONES,

	pc_eq.nombre_equipo AS NOMBRE_EQUIPO,
	pc_eq.marca AS EQUIPO_MARCA,
	pc_eq.serial AS EQUIPO_SERIAL,
	pc_eq.modelo AS EQUIPO_MODELO,

	p.nombre AS PERSONA_NOMBRE,
	p.cedula AS PERSONA_CEDULA, 
	p.telefono AS PERSONAL_TELEFONO,
	p_c.nombre AS PERSONAL_CARGO,

	in_v.nombre AS INVENTARIO_NOMBRE,
	in_v.marca AS INVENTARIO_MARCA,
	in_v.modelo AS INVENTARIO_MODELO,
	in_v.serial AS INVENTARIO_SERIAL

FROM pc_entregas pc_en
LEFT JOIN pc_devuelto pc_dev ON pc_dev.entrega_id = pc_en.id
LEFT JOIN pc_equipos pc_eq ON pc_eq.id = pc_en.equipo_id
LEFT JOIN personal p ON p.id = pc_en.funcionario_id
LEFT JOIN p_cargo p_c ON p_c.id = p.cargo_id
LEFT JOIN pc_perifericos_entregados pc_pe ON pc_pe.entrega_id = pc_en.id
LEFT JOIN inventario in_v ON in_v.id = pc_pe.inventario_id
WHERE pc_en.id = :id

";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $idEntrega]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
	die("No se encontraron datos");
}

// ======================= DATOS GENERALES =======================
$info = $data[0];


$fecha = $info['FECHA_ENTREGA'];

[$year, $month, $day] = explode('-', $fecha);

$sheet->setCellValue("B14", $year);
$sheet->setCellValue("D14", $month);
$sheet->setCellValue("E14", $day);


$sheet->setCellValue("T7", $sheet->getCell("T7")->getValue() . " " . $info['PERSONA_NOMBRE']);
$sheet->setCellValue("T8", $sheet->getCell("T8")->getValue() . " " . $info['PERSONA_CEDULA']);
$sheet->setCellValue("T9", $sheet->getCell("T9")->getValue() . " " . $info['PERSONAL_CARGO']);
$sheet->setCellValue("T10", $sheet->getCell("T10")->getValue() . " " . $info['PERSONAL_TELEFONO']);


$sheet->setCellValue("F14", $info['NOMBRE_EQUIPO']);
$sheet->setCellValue("R14", $info['EQUIPO_MARCA']);
$sheet->setCellValue("V14", $info['EQUIPO_MODELO']);
$sheet->setCellValue("Z14", $info['EQUIPO_SERIAL']);

insertarFirma($sheet, $info['FIRMA_RECIBE'], "AD14");
insertarFirma($sheet, $info['FIRMA_ENTREGA'], "AG14");

// ======================= INVENTARIOS =======================
$startRow = 16;

foreach ($data as $item) {

	if (empty($item['INVENTARIO_NOMBRE'])) continue;
	$fecha = $info['FECHA_ENTREGA'];

	[$year, $month, $day] = explode('-', $fecha);

	$sheet->setCellValue("B{$startRow}", $year);
	$sheet->setCellValue("D{$startRow}", $month);
	$sheet->setCellValue("E{$startRow}", $day);
	$sheet->setCellValue("F{$startRow}", $item['INVENTARIO_NOMBRE']);
	$sheet->setCellValue("R{$startRow}", $item['INVENTARIO_MARCA']);
	$sheet->setCellValue("V{$startRow}", $item['INVENTARIO_MODELO']);
	$sheet->setCellValue("Z{$startRow}", $item['INVENTARIO_SERIAL']);
	$sheet->setCellValue("AJ{$startRow}", $item['FECHA_ENTREGA']);

	insertarFirma($sheet, $item['FIRMA_RECIBE'], "AD{$startRow}");
	insertarFirma($sheet, $item['FIRMA_ENTREGA'], "AG{$startRow}");

	$startRow++;
	$startRow++;
}

// ======================= DESCARGA =======================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="acta_entrega_' . $idEntrega . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// ======================= FUNCION FIRMA =======================

function insertarFirma($sheet, $rutaFirma, $celda)
{ 
	if (empty($rutaFirma)) return;

	$fullPath = __DIR__ . "/../" . ltrim($rutaFirma, '/');
	if (!file_exists($fullPath)) return;

	$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
	$drawing->setPath($fullPath);
	$drawing->setCoordinates($celda);

	// tamaño fijo (no auto)
	$drawing->setResizeProportional(false);
	$drawing->setHeight(45);
	$drawing->setWidth(75);

	// centrado visual
	$drawing->setOffsetX(10);
	$drawing->setOffsetY(2);

	$drawing->setWorksheet($sheet);
}
