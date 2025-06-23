<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_crud.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['usuario_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Se requiere el ID de usuario"]);
        exit;
    }

    $usuarioId = filter_var($data['usuario_id'], FILTER_VALIDATE_INT);
    if ($usuarioId === false) {
        http_response_code(400);
        echo json_encode(["error" => "ID de usuario inválido"]);
        exit;
    }

    $sql = "SELECT COUNT(*) AS total_pendientes
            FROM mantenimientos mf
            WHERE mf.esta_revisado = 0";
    $params = [];

    // Verificar permisos para contar pendientes
    if (tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['CONTAR_TODOS_PENDIENTES'])) {
        // No modificamos nada, ya ve todos
    } elseif (tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['CONTAR_PROPIOS_PENDIENTES'])) {
        $sql .= " AND (mf.creado_por = ? OR mf.nombre_receptor = ?)";
        $params = [$usuarioId, $usuarioId];
    } else {
        http_response_code(403);
        echo json_encode(["error" => "No tienes permiso para ver mantenimientos pendientes"]);
        exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPendientes = (int)$resultado['total_pendientes'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total_pendientes' => $totalPendientes
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al contar mantenimientos pendientes: ' . $e->getMessage()
    ]);
}
