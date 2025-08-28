<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoRechazoPedido.php';

// Leer el JSON
$data = json_decode(file_get_contents("php://input"), true);
$tipoRechazo = $data['tipo_rechazo'] ?? 'compras';

if ($tipoRechazo === 'gerencia') {
    $campoEstado = 'estado_gerencia';
} else {
    $campoEstado = 'estado_compras';
}

$data[$campoEstado] = 'rechazado';

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}
$usuarioId = $data['id_usuario'] ?? null;

// Permiso para rechazar
if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['RECHAZAR_PEDIDO'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tienes permisos para aprobar pedidos"]);
    exit;
}

// Validar campos obligatorios
$campos_obligatorios = ['id_usuario', 'id_pedido', 'observacion_diligenciado'];
foreach ($campos_obligatorios as $campo) {
    if (empty($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo $campo es obligatorio"]);
        exit;
    }
}

// Forzar estado a "rechazado"
$data['estado_compras'] = 'rechazado';

try {
    // Actualizar pedido
    $sqlUpdate = "
    UPDATE cp_pedidos
    SET $campoEstado = :estado,
        observacion_diligenciado = :observacion_diligenciado
    WHERE id = :id_pedido
";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':estado' => 'rechazado',
        ':observacion_diligenciado' => $data['observacion_diligenciado'],
        ':id_pedido' => $data['id_pedido']
    ]);

    // Obtener datos del pedido para el correo
    $sqlPedido = "
    SELECT p.fecha_solicitud, 
           p.proceso_solicitante, 
           ts.nombre AS nombre_tipo,   -- 👈 traes el nombre
           p.consecutivo,
           p.observacion_diligenciado, 
           u.nombre_completo, 
           u.correo
    FROM cp_pedidos AS p
    JOIN usuarios AS u ON p.creador_por = u.id
    LEFT JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
    WHERE p.id = :id_pedido
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
        "Rechazó el pedido con consecutivo {$pedido['consecutivo']}",
        "cp_pedidos",
        $data['id_pedido']
    );

    // Enviar notificación al usuario 
    enviarCorreoRechazoPedido(
        $pedido['correo'],
        $pedido['nombre_completo'],
        $pedido['fecha_solicitud'],
        $pedido['proceso_solicitante'],
        $pedido['nombre_tipo'],
        $pedido['observacion_diligenciado'],
        $pedido['consecutivo']
    );

    echo json_encode([
        "success" => true,
        "message" => "Pedido rechazado y notificaciones enviadas"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al rechazar el pedido: " . $e->getMessage()]);
}
