<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $termino = strtolower(trim($_GET['q'] ?? ''));

    if (!$termino || strlen($termino) < 2) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT id, nombre, cedula, telefono, cargo, proceso
            FROM personal
            WHERE LOWER(cedula) LIKE :busqueda
               OR LOWER(nombre) LIKE :busqueda
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$termino%";
    $stmt->bindValue(':busqueda', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($funcionarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al buscar funcionario',
        'error' => $e->getMessage()
    ]);
}
