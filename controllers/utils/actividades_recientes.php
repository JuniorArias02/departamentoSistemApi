<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_getPost.php';


try {
    $stmt = $pdo->prepare("SELECT 
        a.id, 
        u.nombre_completo as user,
        a.accion as action,
        a.fecha
        FROM actividades a
        JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY a.fecha DESC
        LIMIT 20");

    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($actividades);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener actividades: " . $e->getMessage()]);
}