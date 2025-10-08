<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoAprobadoPedido.php';
require_once __DIR__ . '/../notif_notificaciones/notificarNuevoPedidoGerenteComercial.php';

// Leer el JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

$usuarioId = $data['id_usuario'] ?? null;
$tipoAprobacion = $data['tipo'] ?? 'compra';
$motivo = $data['motivo_aprobacion'] ?? null; // <-- Nuevo campo opcional

// Permiso para aprobar
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
$campos_obligatorios = ['id_usuario', 'id_pedido', 'tipo'];
foreach ($campos_obligatorios as $campo) {
    if (empty($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo $campo es obligatorio"]);
        exit;
    }
}

// Forzar estado según tipo
if ($tipoAprobacion === 'compra') {
    $campoEstado = 'estado_compras';
    $campoFirma = 'proceso_compra_firma';
    $data['estado_compras'] = 'aprobado';
    $data['proceso_compra'] = $usuarioId;
} else { // gerencia
    $campoEstado = 'estado_gerencia';
    $campoFirma = 'responsable_aprobacion_firma';
    $data['estado_gerencia'] = 'aprobado';
    $data['responsable_aprobacion'] = $usuarioId;
}

try {
    // Actualizar pedido con motivo
    $sqlUpdate = "
        UPDATE cp_pedidos
        SET $campoEstado = :estado,
            " . ($tipoAprobacion === 'compra' ? 'proceso_compra' : 'responsable_aprobacion') . " = :usuario,
            motivo_aprobacion = :motivo
        WHERE id = :id_pedido
    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':estado' => $data[$campoEstado],
        ':usuario' => $usuarioId,
        ':motivo' => $motivo,
        ':id_pedido' => $data['id_pedido']
    ]);

    // Obtener datos del pedido para el correo
    $sqlPedido = "
        SELECT p.fecha_solicitud, 
               ds.nombre AS proceso_solicitante, 
               p.tipo_solicitud, 
               ts.nombre AS nombre_tipo, 
               p.consecutivo, 
               p.observacion,
               p.motivo_aprobacion
        FROM cp_pedidos p
        LEFT JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
        LEFT JOIN dependencias_sedes ds ON ds.id = p.proceso_solicitante
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

    registrarActividad(
        $pdo,
        $data['id_usuario'],
        "Aprobó el pedido con consecutivo {$pedido['consecutivo']}",
        "cp_pedidos",
        $data['id_pedido']
    );

    // Enviar correo al creador
    if ($usuarioCreador) {
        enviarCorreoAprobacionPedido(
            $usuarioCreador['correo'],
            $usuarioCreador['nombre_completo'],
            $pedido['fecha_solicitud'],
            $pedido['proceso_solicitante'],
            $pedido['nombre_tipo'],
            $pedido['observacion'] ?? '',
            $pedido['consecutivo'],
            $pedido['motivo_aprobacion'] ?? ''
        );
    }

    // Notificar a gerente comercial si aplica
    if ($tipoAprobacion === 'compra') {
        notificarNuevoPedidoGerenteComercial(
            $pdo,
            $pedido['fecha_solicitud'],
            $pedido['proceso_solicitante'],
            $pedido['nombre_tipo'],
            $pedido['observacion'] ?? '',
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
