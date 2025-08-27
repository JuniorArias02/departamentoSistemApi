<?php
function bloquear_ip($pdo, $ip, $minutosBloqueo)
{
    $stmt = $pdo->prepare("
        INSERT INTO sec_ip_bloqueadas (ip, fecha_bloqueo, fecha_expiracion)
        VALUES (:ip, NOW(), DATE_ADD(NOW(), INTERVAL :min MINUTE))
    ");
    $stmt->execute([
        'ip' => $ip,
        'min' => $minutosBloqueo
    ]);
}
