<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_crud.php';


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

    // Obtener el rol del usuario
    $stmtRol = $pdo->prepare("
        SELECT r.nombre AS nombre_rol 
        FROM usuarios u
        JOIN rol r ON u.rol_id = r.id
        WHERE u.id = ?
    ");
    $stmtRol->execute([$usuarioId]);
    $usuario = $stmtRol->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(["error" => "Usuario no encontrado"]);
        exit;
    }

    $nombreRol = $usuario['nombre_rol'];

    // Consulta para contar mantenimientos NO revisados (esta_revisado = 0)
    $sql = "SELECT COUNT(*) AS total_pendientes
            FROM mantenimientos mf
            WHERE mf.esta_revisado = 0";

    // Filtros según el rol del usuario
    $params = [];
    switch ($nombreRol) {
        case 'administrador':
        case 'gerencia':
            break;
            
        case 'coordinador':
            // Solo ven los que ellos crearon o les asignaron
            $sql .= " AND (mf.creado_por = ? OR mf.nombre_receptor = ?)";
            $params = [$usuarioId, $usuarioId];
            break;
            
        case 'Invitado':
            http_response_code(403);
            echo json_encode(["error" => "No tienes permisos para ver estas notificaciones"]);
            exit;
            
        default:
            http_response_code(403);
            echo json_encode(["error" => "Rol no reconocido: " . $nombreRol]);
            exit;
    }

    $stmt = $pdo->prepare($sql);
    
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPendientes = (int)$resultado['total_pendientes'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total_pendientes' => $totalPendientes,
        'rol_usuario' => $nombreRol 
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al contar mantenimientos pendientes: ' . $e->getMessage()
    ]);
}