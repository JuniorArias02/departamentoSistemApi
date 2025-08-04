<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $termino = strtolower(trim($_GET['q'] ?? ''));

    if (!$termino || strlen($termino) < 2) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT id, nombre_equipo, marca, modelo, serial, numero_inventario 
            FROM pc_equipos 
            WHERE LOWER(serial) LIKE :busqueda 
               OR LOWER(numero_inventario) LIKE :busqueda
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$termino%";
    $stmt->bindValue(':busqueda', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($equipos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar equipo',
        'error' => $e->getMessage()
    ]);
}
