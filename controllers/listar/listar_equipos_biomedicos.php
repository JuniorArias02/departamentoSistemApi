<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

try {
    $sql = "SELECT 
                id,
                nombre_equipo, 
                marca, 
                modelo, 
                serie, 
                registro_sanitario, 
                clasificacion_riesgo 
            FROM equipos_biomedicos";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($equipos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
?>
