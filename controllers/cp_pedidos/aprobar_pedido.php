<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoAprobadoPedido.php';

// Leer el JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}
$usuarioId = $data['id_usuario'] ?? null;

// Permiso para rechazar
if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['APROBAR_PEDIDO'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tienes permisos para aprobar pedidos"]);
    exit;
}

// Obtener el usuario que creó el pedido
$sqlCreador = "
    SELECT u.id, u.nombre_completo, u.correo
    FROM usuarios u
    INNER JOIN cp_pedidos p ON p.creador_por = u.id
    WHERE p.id = :id_pedido
    LIMIT 1
";
$stmtCreador = $pdo->prepare($sqlCreador);
$stmtCreador->execute([':id_pedido' => $data['id_pedido']]);
$usuarioCreador = $stmtCreador->fetch(PDO::FETCH_ASSOC);


// Validar campos obligatorios
$campos_obligatorios = ['id_usuario', 'id_pedido'];
foreach ($campos_obligatorios as $campo) {
    if (empty($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo $campo es obligatorio"]);
        exit;
    }
}

// Forzar estado a "aprobado"
$data['estado_compras'] = 'aprobado';
$data['proceso_compra'] = $usuarioId; 

try {
    // Actualizar pedido
    $sqlUpdate = "
        UPDATE cp_pedidos
        SET estado_compras = :estado_compras,
            proceso_compra = :proceso_compra
        WHERE id = :id_pedido
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':estado_compras' => $data['estado_compras'],
        ':proceso_compra' => $data['proceso_compra'], 
        ':id_pedido' => $data['id_pedido']
    ]);

    // Obtener datos del pedido para el correo
    $sqlPedido = "
        SELECT fecha_solicitud, proceso_solicitante, tipo_solicitud, consecutivo
        FROM cp_pedidos
        WHERE id = :id_pedido
    ";
    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute([':id_pedido' => $data['id_pedido']]);
    $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(["error" => "Pedido no encontrado"]);
        exit;
    }

    // Registrar actividad
    registrarActividad(
        $pdo,
        $data['id_usuario'],
        "Aprobó el pedido con consecutivo {$pedido['consecutivo']}",
        "cp_pedidos",
        $data['id_pedido']
    );

    if ($usuarioCreador) {
        enviarCorreoAprobacionPedido(
            $usuarioCreador['correo'],
            $usuarioCreador['nombre_completo'],
            $pedido['fecha_solicitud'],
            $pedido['proceso_solicitante'],
            $pedido['tipo_solicitud'],
            "",
            $pedido['consecutivo']
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Pedido aprobado correctamente"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al aprobar el pedido: " . $e->getMessage()]);
}