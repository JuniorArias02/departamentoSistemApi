<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo aceptar GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

// Obtener el código desde la URL (?codigo=PROD001)
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : null;

if (!$codigo) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Debes enviar el código del producto"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nombre 
        FROM cp_productos 
        WHERE codigo = :codigo
        LIMIT 1
    ");
    $stmt->execute([':codigo' => $codigo]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        echo json_encode([
            "success" => true,
            "producto" => $producto
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "Producto no encontrado"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al buscar producto: " . $e->getMessage()
    ]);
}
