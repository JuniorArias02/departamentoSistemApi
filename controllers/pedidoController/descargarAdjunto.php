<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Validar ID
if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro 'id'"]);
    exit;
}

$pedidoId = intval($_GET['id']);

// Buscar ruta en la BD
$stmt = $pdo->prepare("SELECT adjunto_pdf FROM cp_pedidos WHERE id = ?");
$stmt->execute([$pedidoId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido || empty($pedido['adjunto_pdf'])) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontró el adjunto"]);
    exit;
}

// Ruta absoluta real del archivo
$rutaArchivo = __DIR__ . '/../../' . $pedido['adjunto_pdf'];

if (!file_exists($rutaArchivo)) {
    http_response_code(404);
    echo json_encode(["error" => "El archivo no existe"]);
    exit;
}

// 🔹 Importante: limpiar cualquier buffer antes de enviar el archivo
if (ob_get_level()) ob_end_clean();

// Forzar descarga
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
header('Content-Length: ' . filesize($rutaArchivo));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($rutaArchivo);
exit;
