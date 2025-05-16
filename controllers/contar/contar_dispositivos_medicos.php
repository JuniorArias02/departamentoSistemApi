<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");
require_once '../../database/conexion.php';

try {
    $sql = "SELECT COUNT(*) AS total FROM dispositivos_medicos";
    $stmt = $pdo->prepare($sql); // usa $pdo, no $conn
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['total' => $resultado['total']]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al contar dispositivos: ' . $e->getMessage()]);
}
