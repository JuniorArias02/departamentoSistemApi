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
                nombre, 
                marca, 
                presentacion_comercial, 
                registro_sanitario, 
                clasificacion_riesgo, 
                vida_util, 
                fecha_vencimiento, 
                lote
            FROM reactivo_vigilancia";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $reactivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reactivos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
?>
