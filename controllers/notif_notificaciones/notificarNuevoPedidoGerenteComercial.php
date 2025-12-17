<?php
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoNuevoPedidoGerenteComercial.php';

/**
 * Notificar a todos los usuarios que tengan el permiso de recibir pedidos nuevos.
 *
 * @param PDO $pdo Conexión a la base de datos
 * @param string $fechaSolicitud
 * @param string $procesoSolicitante
 * @param string $tipoSolicitud
 * @param string $observacion
 * @param int|null $consecutivo
 * @return void
 */
function notificarNuevoPedidoGerenteComercial(PDO $pdo, int $idPedido)
{
    $permisoClave = PERMISOS['GESTION_COMPRA_PEDIDOS']['VER_PEDIDOS_ENCARGADO'];

    // Datos del pedido
    $stmtPedido = $pdo->prepare("
        SELECT 
            p.fecha_solicitud,
            ds.nombre AS proceso_solicitante,
            ts.nombre AS tipo_solicitud,
            p.observacion,
            p.consecutivo
        FROM cp_pedidos p
        LEFT JOIN dependencias_sedes ds ON ds.id = p.proceso_solicitante
        LEFT JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
        WHERE p.id = :id
    ");
    $stmtPedido->execute([':id' => $idPedido]);
    $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) return;

    // Usuarios con permiso
    $stmt = $pdo->prepare("
        SELECT u.nombre_completo, u.correo
        FROM usuarios u
        JOIN rol_permisos rp ON u.rol_id = rp.rol_id
        JOIN permisos p ON rp.permiso_id = p.id
        WHERE p.nombre = :permiso
          AND u.estado = 1
    ");
    $stmt->execute(['permiso' => $permisoClave]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        enviarCorreoNuevoPedidoGerenteComercial(
            $usuario['correo'],
            $usuario['nombre_completo'],
            $pedido['fecha_solicitud'],
            $pedido['proceso_solicitante'],
            $pedido['tipo_solicitud'],
            $pedido['observacion'] ?? '',
            $pedido['consecutivo']
        );
    }
}
