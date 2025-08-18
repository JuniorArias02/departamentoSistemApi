<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
// Headers CORS y tipo de respuesta
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Responder el preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Recibir datos del body
$data = json_decode(file_get_contents("php://input"), true);
$idPedido = isset($data['id']) ? intval($data['id']) : 0;

if ($idPedido <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "ID de pedido inválido"]);
    exit;
}

// Consulta
$sqlPedido = "
SELECT 
    p.fecha_solicitud AS fecha,
    p.consecutivo,
    ts.nombre AS tipo_solicitud,
    p.observacion AS observaciones,
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

$stmt = $pdo->prepare($sqlPedido);
$stmt->execute([':id' => $idPedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontró el pedido"]);
    exit;
}

// Datos generales del pedido

// --- Items ---
$sqlItems = "
SELECT nombre, cantidad, referencia_items AS referencia
FROM cp_items_pedidos
WHERE cp_pedido = :id
";

$stmtItems = $pdo->prepare($sqlItems);
$stmtItems->execute([':id' => $idPedido]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);


// --- Cargar plantilla ---
$templatePath = __DIR__ . "/../public/plantilla_pedidos.xlsx";
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// --- CAMPOS ENCABEZADO ---
$sheet->setCellValue("D5", $pedido['fecha']);
$sheet->setCellValue("H5", $pedido['consecutivo']);

if ($pedido['tipo_solicitud'] === "Prioritaria") {
    $sheet->setCellValue("I8", "X");
} elseif ($pedido['tipo_solicitud'] === "Recurrente") {
    $sheet->setCellValue("F8", "X");
}

$sheet->setCellValue("A18", $pedido['observaciones']);




// --- Items (empiezan en la fila 11) ---
$startRow = 11;
$count = 0;
foreach ($items as $i => $item) {
    $row = $startRow + $i;
    $count++;
    $sheet->setCellValue("A{$row}", $count);
    $sheet->setCellValue("B{$row}", $item['nombre']);
    $sheet->setCellValue("H{$row}", "unidades");
    $sheet->setCellValue("I{$row}", $item['cantidad']);
    $sheet->setCellValue("J{$row}", $item['referencia']); // por si quieres mostrar la referencia
}

// --- Firmas como imágenes ---


define("BASE_PATH", __DIR__ . "/../");

function insertarFirma($sheet, $rutaFirma, $celda)
{
    if (empty($rutaFirma)) {
        return; // no hay firma
    }

    $fullPath = BASE_PATH . $rutaFirma;
    if (!file_exists($fullPath)) {
        return; // no existe
    }

    // Extraer fila y columna
    preg_match('/([A-Z]+)([0-9]+)/', $celda, $matches);
    $col = $matches[1];
    $row = (int)$matches[2];

    // Altura de la fila (si no hay, usar default)
    $filaHeight = $sheet->getRowDimension($row)->getRowHeight();
    if ($filaHeight <= 0) {
        $filaHeight = 60; // fallback
    }

    // Relación fija 500x400 = 1.25
    $aspectRatio = 1.25;
    $newHeight = $filaHeight; 
    $newWidth  = $filaHeight * $aspectRatio;

    // Ajustar la fila para que quede exacta
    $sheet->getRowDimension($row)->setRowHeight($newHeight);

    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setPath($fullPath);
    $drawing->setCoordinates($celda);
    $drawing->setHeight($newHeight);
    $drawing->setWidth($newWidth);
    $drawing->setResizeProportional(false);

    // Margen (ajusta estos valores a tu gusto)
    $drawing->setOffsetX(60); // mover a la derecha
    $drawing->setOffsetY(15);  // mover hacia abajo

    $drawing->setWorksheet($sheet);
}



insertarFirma($sheet, $pedido['elaborado_firma'], "A23");

// Proceso de compra
insertarFirma($sheet, $pedido['proceso_compra_firma'], "B25");

// Responsable
insertarFirma($sheet, $pedido['responsable_firma'], "B30");

// --- Descargar Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="pedido.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
