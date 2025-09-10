<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

$data = json_decode(file_get_contents("php://input"), true);
$search = trim($data['search'] ?? '');

if (empty($search)) {
    echo json_encode([
        "success" => false,
        "message" => "Debes enviar un nombre o NIT para buscar"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nombre, nit, telefono, correo, direccion
        FROM cp_proveedores
        WHERE nombre LIKE :search OR nit LIKE :search
        ORDER BY nombre ASC
    ");
    $stmt->execute([":search" => "%$search%"]);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data"    => $proveedores
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en la búsqueda de proveedores",
        "error"   => $e->getMessage()
    ]);
}
  