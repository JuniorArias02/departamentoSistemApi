<?php
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoNuevoPedidoCompras.php';

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
function notificarNuevoPedidoCompras($pdo, $fechaSolicitud, $procesoSolicitante, $tipoSolicitud, $observacion, $consecutivo = null)
{
    $permisoClave = PERMISOS['GESTION_COMPRA_PEDIDOS']['RECIBIR_NUEVOS_PEDIDOS'];

    // Buscar usuarios con el permiso
    $sql = "SELECT u.nombre_completo, u.correo
            FROM usuarios u
            JOIN rol_permisos rp ON u.rol_id = rp.rol_id
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE p.nombre = :permiso
              AND u.estado = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['permiso' => $permisoClave]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enviar correo a cada usuario
    foreach ($usuarios as $usuario) {
        try {
            enviarCorreoNuevoPedidoCompras(
                $usuario['correo'],
                $usuario['nombre_completo'],
                $fechaSolicitud,
                $procesoSolicitante,
                $tipoSolicitud,
                $observacion,
                $consecutivo
            );
        } catch (Exception $e) {
            error_log("Error enviando correo a {$usuario['correo']}: " . $e->getMessage());
        }
    }
}
