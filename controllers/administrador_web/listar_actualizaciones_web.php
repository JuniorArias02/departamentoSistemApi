<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $sql = "SELECT 
                id, titulo, descripcion, 
                mostrar_desde, mostrar_hasta, 
                fecha_actualizacion, duracion_minutos, 
                estado, creado_en
            FROM actualizaciones_web
            ORDER BY fecha_actualizacion DESC";

    $stmt = $pdo->query($sql);
    $actualizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $actualizaciones
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las actualizaciones: ' . $e->getMessage()
    ]);
}
