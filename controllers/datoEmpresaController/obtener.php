<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    // Consulta para traer todos los datos de la empresa
    $sql = "SELECT id, nombre, nit, direccion, telefono, email, representante_legal, ciudad 
            FROM datos_empresa";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empresa) {
        echo json_encode([
            "status" => "success",
            "data" => $empresa
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se encontraron datos de la empresa"
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error en el servidor: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
