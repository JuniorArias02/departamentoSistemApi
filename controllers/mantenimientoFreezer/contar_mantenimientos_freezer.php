<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Consulta directa sin filtros
    $sql = "SELECT COUNT(*) AS total FROM mantenimientos_freezer";
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Respuesta mínima: solo el número total
    echo json_encode(["total" => (int)$resultado['total']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al contar mantenimientos: " . $e->getMessage()]);
}