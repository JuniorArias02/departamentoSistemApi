<?php
require_once '../../database/conexion.php';

// Headers CORS
header("Access-Control-Allow-Origin:https://formulario-medico.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

// Manejo de preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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