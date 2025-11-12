<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $sql = "SELECT 
                u.id,
                u.nombre_completo,
                u.telefono,
                u.correo,
                r.nombre AS rol,
                s.nombre AS sede
            FROM usuarios u
            LEFT JOIN rol r ON r.id = u.rol_id
            LEFT JOIN sedes s ON s.id = u.sede_id";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener listado de funcionarios',
        'error' => $e->getMessage()
    ]);
}
