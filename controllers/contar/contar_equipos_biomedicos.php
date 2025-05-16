<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");
require_once '../../database/conexion.php';


try {
    $sql = "SELECT COUNT(*) AS total FROM equipos_biomedicos";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['total' => $resultado['total']]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al contar equipos: ' . $e->getMessage()]);
}
