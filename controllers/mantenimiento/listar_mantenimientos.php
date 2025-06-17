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

    // Obtener el NOMBRE DEL ROL (texto) en lugar del ID
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

    // Consulta base
    $sql = "SELECT 
                mf.id,
                mf.titulo,
                mf.codigo,
                mf.modelo,
                mf.dependencia,
                mf.sede_id,
                s.nombre AS nombre_sede,
                mf.nombre_receptor,
                ur.nombre_completo AS nombre_receptor_completo,
                mf.imagen,
                mf.descripcion,
                mf.revisado_por,
                urv.nombre_completo AS nombre_revisor,
                mf.fecha_revisado,
                mf.creado_por,
                uc.nombre_completo AS nombre_creador,
                mf.fecha_creacion,
                mf.esta_revisado,
                mf.fecha_ultima_actualizacion
            FROM 
                mantenimientos_freezer mf
            LEFT JOIN 
                sedes s ON mf.sede_id = s.id
            LEFT JOIN 
                usuarios ur ON mf.nombre_receptor = ur.id
            LEFT JOIN 
                usuarios urv ON mf.revisado_por = urv.id
            LEFT JOIN 
                usuarios uc ON mf.creado_por = uc.id";

    // Filtros por NOMBRE DE ROL (texto)
    $params = [];
    switch ($nombreRol) {
        case 'administrador':
        case 'gerencia':
            // Ven todo sin filtros
            break;
            
        case 'coordinador':
            $sql .= " WHERE mf.nombre_receptor = ? OR mf.creado_por = ?";
            $params = [$usuarioId, $usuarioId];
            break;
        case 'Invitado':
            http_response_code(403);
            echo json_encode(["error" => "No tienes permisos para ver estos registros"]);
            exit;
            
        default:
            http_response_code(403);
            echo json_encode(["error" => "Rol no reconocido: " . $nombreRol]);
            exit;
    }

    $sql .= " ORDER BY mf.fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear respuesta
    foreach ($mantenimientos as &$mantenimiento) {
        $mantenimiento['esta_revisado'] = (bool)$mantenimiento['esta_revisado'];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $mantenimientos,
        'count' => count($mantenimientos),
        'rol_usuario' => $nombreRol 
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los mantenimientos: ' . $e->getMessage()
    ]);
}