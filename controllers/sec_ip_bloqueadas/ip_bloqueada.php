<?php
function ip_bloqueada($pdo, $ip)
{
    $stmt = $pdo->prepare("
        SELECT fecha_expiracion 
        FROM sec_ip_bloqueadas 
        WHERE ip = :ip 
          AND fecha_expiracion > NOW()
        ORDER BY fecha_expiracion DESC
        LIMIT 1
    ");
    $stmt->execute(['ip' => $ip]);
    return $stmt->fetchColumn();
}
