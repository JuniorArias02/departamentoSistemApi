<?php 
function buscarUsuariosPorPermiso($pdo, $permisoClave) {
    $sql = "SELECT u.nombre_completo, u.correo
            FROM usuarios u
            JOIN rol_permisos rp ON u.rol_id = rp.rol_id
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE p.nombre = :permiso
              AND u.estado = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['permiso' => $permisoClave]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
