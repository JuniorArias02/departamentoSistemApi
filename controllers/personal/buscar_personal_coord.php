<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $termino = strtolower(trim($_GET['q'] ?? ''));

    if (!$termino || strlen($termino) < 2) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT 
                u.id,
                u.nombre_completo,
                u.telefono,
                u.email,
                u.cedula,
                r.nombre AS rol,
                a.nombre AS area,
                s.nombre AS sede
            FROM usuarios u
            LEFT JOIN rol r ON r.id = u.rol_id
            LEFT JOIN area a ON a.id = u.area_id
            LEFT JOIN sede s ON s.id = u.sede_id
            WHERE LOWER(u.nombre_completo) LIKE :busqueda
               OR LOWER(u.cedula) LIKE :busqueda
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$termino%";
    $stmt->bindValue(':busqueda', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar funcionario',
        'error' => $e->getMessage()
    ]);
}
