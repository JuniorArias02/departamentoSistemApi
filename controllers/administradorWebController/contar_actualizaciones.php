<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

date_default_timezone_set('America/Bogota');

try {
    $hoy = date('Y-m-d');

    $sql = "SELECT * FROM actualizaciones_web 
            WHERE :hoy BETWEEN mostrar_desde AND mostrar_hasta
            ORDER BY fecha_actualizacion ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hoy' => $hoy]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener actualizaciones: ' . $e->getMessage()
    ]);
}
