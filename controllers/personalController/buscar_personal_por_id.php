<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT 
                p.id, 
                p.nombre, 
                p.cedula, 
                p.telefono, 
                c.nombre AS cargo,
                p.proceso
            FROM personal p
            LEFT JOIN p_cargo c ON p.cargo_id = c.id
            WHERE p.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $persona = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($persona) {
        echo json_encode($persona);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar funcionario por id',
        'error' => $e->getMessage()
    ]);
}
