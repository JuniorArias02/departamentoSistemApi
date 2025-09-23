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
                id,
                codigo,
                nombre,
                serial,
                marca,
                modelo,
                sede_id
            FROM inventario
            WHERE LOWER(codigo) LIKE :busqueda
               OR LOWER(serial) LIKE :busqueda
               OR LOWER(nombre) LIKE :busqueda
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$termino%";
    $stmt->bindValue(':busqueda', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($items);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar inventario',
        'error' => $e->getMessage()
    ]);
}
