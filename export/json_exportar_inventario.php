<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

require_once '../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
$sql = "SELECT i.id, i.codigo, i.nombre, i.dependencia, i.responsable,
               i.marca, i.modelo, i.serial, s.nombre AS sede,
               u.nombre_completo AS creado_por, i.fecha_creacion
        FROM inventario i
        LEFT JOIN sedes s ON i.sede_id = s.id
        LEFT JOIN usuarios u ON i.creado_por = u.id";


    $stmt = $pdo->query($sql);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($datos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "mensaje" => "Error al obtener los datos: " . $e->getMessage()
    ]);
}
