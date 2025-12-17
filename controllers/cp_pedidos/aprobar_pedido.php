<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoAprobadoPedido.php';
require_once __DIR__ . '/../notif_notificaciones/notificarNuevoPedidoGerenteComercial.php';

$data = json_decode(file_get_contents("php://input"), true);

$idPedido  = $data['id_pedido'] ?? null;
$usuarioId = $data['id_usuario'] ?? null;
$observacion = $data['observacion'] ?? null;

if (!$idPedido || !$usuarioId) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

/* ================== PERMISOS ================== */
$puedeCompras = tienePermiso(
    $pdo,
    $usuarioId,
    PERMISOS['GESTION_COMPRA_PEDIDOS']['RECIBIR_NUEVOS_PEDIDOS']
);

$puedeGerente = tienePermiso(
    $pdo,
    $usuarioId,
    PERMISOS['GESTION_COMPRA_PEDIDOS']['VER_PEDIDOS_ENCARGADO']
);

/* ================== ESTADO PEDIDO ================== */
$stmt = $pdo->prepare("
    SELECT estado_compras, estado_gerencia
    FROM cp_pedidos
    WHERE id = :id
");
$stmt->execute([':id' => $idPedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    http_response_code(404);
    echo json_encode(["error" => "Pedido no encontrado"]);
    exit;
}

/* ================== APROBACIÓN COMPRAS ================== */
if ($puedeCompras && $pedido['estado_compras'] === 'pendiente') {

    $stmt = $pdo->prepare("
        UPDATE cp_pedidos
        SET estado_compras = 'aprobado',
            proceso_compra = :usuario,
            motivo_aprobacion = :obs
        WHERE id = :id
    ");
    $stmt->execute([
        ':usuario' => $usuarioId,
        ':obs' => $observacion,
        ':id' => $idPedido
    ]);

    notificarNuevoPedidoGerenteComercial($pdo, $idPedido);

    echo json_encode(["success" => true, "message" => "Pedido aprobado por compras"]);
    exit;
}

/* ================== APROBACIÓN GERENCIA ================== */
if (
    $puedeGerente &&
    $pedido['estado_compras'] === 'aprobado' &&
    $pedido['estado_gerencia'] === 'pendiente'
) {

    $stmt = $pdo->prepare("
        UPDATE cp_pedidos
        SET estado_gerencia = 'aprobado',
            responsable_aprobacion = :usuario,
            observacion_gerencia = :obs
        WHERE id = :id
    ");
    $stmt->execute([
        ':usuario' => $usuarioId,
        ':obs' => $observacion,
        ':id' => $idPedido
    ]);

    echo json_encode(["success" => true, "message" => "Pedido aprobado por gerencia"]);
    exit;
}

/* ================== BLOQUEO ================== */
http_response_code(403);
echo json_encode(["error" => "No autorizado o estado inválido"]);
