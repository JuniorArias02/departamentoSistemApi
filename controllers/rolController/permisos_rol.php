<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

if (!isset($_GET['rol_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere el parámetro rol_id"]);
    exit;
}

try {
    $rol_id = $_GET['rol_id'];
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.nombre, p.descripcion, 
               EXISTS(SELECT 1 FROM rol_permisos rp WHERE rp.rol_id = ? AND rp.permiso_id = p.id) as asignado
        FROM permisos p
        ORDER BY p.nombre
    ");
    $stmt->execute([$rol_id]);
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($permisos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener permisos: " . $e->getMessage()]);
}