 <?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

// Leer el body JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Datos inválidos"
    ]);
    exit;
}

// Validar obligatorios
if (empty($data['reporte_id']) || empty($data['usuario_id']) || empty($data['comentario'])) {
    echo json_encode([
        "success" => false,
        "message" => "reporte_id, usuario_id y comentario son obligatorios"
    ]);
    exit;
}

try {
    $stmt = $conexion->prepare("
        INSERT INTO rf_reportes_comentarios (reporte_id, usuario_id, comentario, fecha)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "iis",
        $data['reporte_id'],
        $data['usuario_id'],
        $data['comentario']
    );

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Comentario agregado con éxito",
            "comentario_id" => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error al agregar comentario"
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
