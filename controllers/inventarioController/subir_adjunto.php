<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';

date_default_timezone_set('America/Bogota');

// Verificar que se haya enviado un archivo y un id
if (!isset($_POST['inventario_id']) || !isset($_FILES['archivo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos (inventario_id o archivo)']);
    exit;
}

$inventario_id = intval($_POST['inventario_id']);
$archivo = $_FILES['archivo'];

// Verificar que el archivo sea un PDF
$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF']);
    exit;
}

// Crear la ruta si no existe
$uploadDir = __DIR__ . '/../../public/inventario/adjunto/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Crear nombre único
$nombreArchivo = 'soporte_' . $inventario_id . '_' . time() . '.pdf';
$rutaDestino = $uploadDir . $nombreArchivo;

// Mover archivo al destino
if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    // Guardar ruta en la BD
    $rutaDB = 'public/inventario/adjunto/' . $nombreArchivo;
    $stmt = $pdo->prepare("UPDATE inventario SET soporte_adjunto = ?, fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->execute([$rutaDB, $inventario_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Archivo cargado correctamente',
        'ruta' => $rutaDB
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al mover el archivo']);
}
