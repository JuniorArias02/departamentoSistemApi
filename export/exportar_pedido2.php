<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// --- Configuración base ---
define("BASE_PATH", __DIR__ . "/../");

// --- Headers CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

/* ==========================================================
   FUNCIONES
========================================================== */

// 1. --- DB ---
function getPedido($pdo, $idPedido)
{
	$sql = "
        SELECT 
            p.fecha_solicitud AS fecha,
            p.consecutivo,
			p.proceso_solicitante,
            ts.nombre AS tipo_solicitud,
            p.observacion AS observaciones,
			p.fecha_compra,
			p.fecha_gerencia,
            u_elab.nombre_completo AS elaborado_nombre,
            r_elab.nombre AS elaborado_cargo,
            p.elaborado_por_firma AS elaborado_firma,
            u_compra.nombre_completo AS proceso_compra_nombre,
            r_compra.nombre AS proceso_compra_cargo,
            p.proceso_compra_firma AS proceso_compra_firma,
            p.fecha_solicitud AS proceso_compra_fecha,
            u_resp.nombre_completo AS responsable_nombre,
            r_resp.nombre AS responsable_cargo,
            p.responsable_aprobacion_firma AS responsable_firma,
            p.fecha_solicitud AS responsable_fecha
        FROM cp_pedidos p
        INNER JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
        LEFT JOIN usuarios u_elab ON u_elab.id = p.elaborado_por
        LEFT JOIN rol r_elab ON r_elab.id = u_elab.rol_id
        LEFT JOIN usuarios u_compra ON u_compra.id = p.proceso_compra
        LEFT JOIN rol r_compra ON r_compra.id = u_compra.rol_id
        LEFT JOIN usuarios u_resp ON u_resp.id = p.responsable_aprobacion
        LEFT JOIN rol r_resp ON r_resp.id = u_resp.rol_id
        WHERE p.id = :id;
    ";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([':id' => $idPedido]);
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getItems($pdo, $idPedido)
{
	$sql = "SELECT nombre, cantidad, referencia_items AS referencia
            FROM cp_items_pedidos WHERE cp_pedido = :id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([':id' => $idPedido]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. --- Excel helpers ---
function llenarEncabezado($sheet, $pedido)
{
	// Convertir fecha al formato de Excel
	$date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($pedido['fecha']));
	$sheet->setCellValue("D5", $date);

	// Aplicar formato dd/mm/aaaa
	$sheet->getStyle("D5")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

	$sheet->setCellValue("D6", $pedido['proceso_solicitante']);
	$sheet->setCellValue("H5", $pedido['consecutivo']);

	if ($pedido['tipo_solicitud'] === "Prioritaria") {
		$sheet->setCellValue("I8", "X");
	} elseif ($pedido['tipo_solicitud'] === "Recurrente") {
		$sheet->setCellValue("F8", "X");
	}

	$currentObs = $sheet->getCell("A18")->getValue();
	if (!empty($currentObs)) {
		$sheet->setCellValue("A18", $currentObs . " " . $pedido['observaciones']);
	} else {
		$sheet->setCellValue("A18", $pedido['observaciones']);
	}
}

function llenarItems($sheet, $items, $startRow = 11)
{
	$count = 0;
	foreach ($items as $i => $item) {
		$row = $startRow + $i;
		$count++;
		$sheet->setCellValue("A{$row}", $count);
		$sheet->setCellValue("B{$row}", $item['nombre']);
		$sheet->setCellValue("H{$row}", "unidades");
		$sheet->setCellValue("I{$row}", $item['cantidad']);
		$sheet->setCellValue("J{$row}", $item['referencia']);
	}
}


function responsableProceso($sheet, $pedidos)
{
    // Helper para formatear fecha en dd/mm/aaaa
    $formatFecha = function ($fecha) {
        if (empty($fecha)) return "";
        return (new DateTime($fecha))->format("d/m/Y");
    };

    // Helper para concatenar
    $concat = function ($cell, $value) use ($sheet) {
        if (!empty($value)) {
            $current = $sheet->getCell($cell)->getValue();
            if (!empty($current)) {
                $sheet->setCellValue($cell, $current . " " . $value);
            } else {
                $sheet->setCellValue($cell, $value);
            }
        }
    };

    // Fechas concatenadas como texto
    $concat("A34", "" . $formatFecha($pedidos['fecha_compra']));
    $concat("F34", "" . $formatFecha($pedidos['fecha_gerencia']));

    // Nombres y cargos
    $concat("A25", $pedidos['elaborado_nombre']);
    $concat("A26", $pedidos['elaborado_cargo']);
    $concat("A32", $pedidos['proceso_compra_nombre']);
    $concat("A33", $pedidos['proceso_compra_cargo']);
    $concat("F32", $pedidos['responsable_nombre']);
    $concat("F33", $pedidos['responsable_cargo']);
}



function insertarFirma($sheet, $rutaFirma, $celda)
{
	if (empty($rutaFirma)) return;

	$fullPath = BASE_PATH . $rutaFirma;
	if (!file_exists($fullPath)) return;

	preg_match('/([A-Z]+)([0-9]+)/', $celda, $matches);
	$row = (int)$matches[2];

	$filaHeight = $sheet->getRowDimension($row)->getRowHeight();
	if ($filaHeight <= 0) $filaHeight = 60;

	$aspectRatio = 1.25; // 500x400
	$newHeight = $filaHeight;
	$newWidth  = $filaHeight * $aspectRatio;

	$sheet->getRowDimension($row)->setRowHeight($newHeight);

	$drawing = new Drawing();
	$drawing->setPath($fullPath);
	$drawing->setCoordinates($celda);
	$drawing->setHeight($newHeight);
	$drawing->setWidth($newWidth);
	$drawing->setResizeProportional(false);
	$drawing->setOffsetX(60);
	$drawing->setOffsetY(15);
	$drawing->setWorksheet($sheet);
}

function insertarFirmas($sheet, $pedido)
{
	insertarFirma($sheet, $pedido['elaborado_firma'], "A23");
	insertarFirma($sheet, $pedido['proceso_compra_firma'], "A30");
	insertarFirma($sheet, $pedido['responsable_firma'], "F30");
}

/* ==========================================================
   CONTROLADOR PRINCIPAL
========================================================== */
$data = json_decode(file_get_contents("php://input"), true);
$idPedido = isset($data['id']) ? intval($data['id']) : 0;

if ($idPedido <= 0) {
	http_response_code(400);
	echo json_encode(["error" => "ID de pedido inválido"]);
	exit;
}

$pedido = getPedido($pdo, $idPedido);
if (!$pedido) {
	http_response_code(404);
	echo json_encode(["error" => "No se encontró el pedido"]);
	exit;
}

$items = getItems($pdo, $idPedido);

// Plantilla
$templatePath = __DIR__ . "/../public/plantilla_pedidos.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// Llenar datos
llenarEncabezado($sheet, $pedido);
llenarItems($sheet, $items);
insertarFirmas($sheet, $pedido);
responsableProceso($sheet, $pedido);
// Exportar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="pedido.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
