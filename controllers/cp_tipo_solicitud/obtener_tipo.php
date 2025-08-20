<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $stmt = $pdo->query("SELECT * FROM cp_tipo_solicitud ORDER BY id DESC");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tipos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
