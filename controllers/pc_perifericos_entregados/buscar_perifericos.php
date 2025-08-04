<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $filtro = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

    $sql = "SELECT id, codigo, nombre, serial, marca, modelo
            FROM inventario
            WHERE 
                LOWER(codigo) LIKE :filtro OR 
                LOWER(nombre) LIKE :filtro OR 
                LOWER(serial) LIKE :filtro
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':filtro', "%" . strtolower($filtro) . "%", PDO::PARAM_STR);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => $resultados
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar periféricos',
        'error' => $e->getMessage()
    ]);
}
