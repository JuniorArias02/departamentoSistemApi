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
                principio_activo, 
                forma_farmaceutica, 
                concentracion, 
                lote, 
                fecha_vencimiento, 
                presentacion_comercial, 
                unidad_medida, 
                registro_sanitario
            FROM medicamentos";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($medicamentos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
?>
