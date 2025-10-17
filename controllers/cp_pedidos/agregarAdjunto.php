<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Carpeta donde se guardarán los adjuntos
$uploadDir = __DIR__ . '/../../public/pedidosAdjuntos/';

// Crear carpeta si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Verificar si se envió archivo y pedido_id
if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK || empty($_POST['pedido_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos (archivo o pedido_id faltante)"]);
    exit;
}

$file = $_FILES['archivo'];
$pedidoId = intval($_POST['pedido_id']);

// Validar extensión PDF
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    http_response_code(400);
    echo json_encode(["error" => "Solo se permiten archivos PDF"]);
    exit;
}

// Validar MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(["error" => "El archivo no es un PDF válido"]);
    exit;
}

// Generar nombre único
$nombreArchivo = uniqid('adjunto_', true) . '.pdf';
$rutaDestino = $uploadDir . $nombreArchivo;

// Mover archivo
if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar el archivo"]);
    exit;
}

// Ruta relativa pública
$rutaRelativa = 'public/pedidosAdjuntos/' . $nombreArchivo;

// 🔹 Actualizar pedido con la ruta del archivo
$stmt = $pdo->prepare("UPDATE cp_pedidos SET adjunto_pdf = ? WHERE id = ?");
$ok = $stmt->execute([$rutaRelativa, $pedidoId]);

if (!$ok) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo actualizar el pedido"]);
    exit;
}

echo json_encode([
    "mensaje" => "Archivo subido y asociado correctamente al pedido",
    "ruta" => $rutaRelativa,
    "pedido_id" => $pedidoId
]);
