<?php
function tienePermiso($pdo, $usuarioId, $permisoNombre) {
    $sql = "SELECT COUNT(*) FROM usuarios u
            JOIN rol_permisos rp ON u.rol_id = rp.rol_id
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE u.id = ? AND p.nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuarioId, $permisoNombre]);
    return $stmt->fetchColumn() > 0;
}
