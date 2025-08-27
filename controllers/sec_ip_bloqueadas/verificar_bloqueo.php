<?php
require_once __DIR__ . '/ip_bloqueada.php';
require_once __DIR__ . '/bloquear_ip.php';
function verificar_bloqueo($pdo, $ip, $maxIntentos = 5, $ventanaMinutos = 10, $minutosBloqueo = 3)
{
	// 1. Verificar si ya está bloqueada
	$expiracion = ip_bloqueada($pdo, $ip);
	if ($expiracion) {
		return $expiracion; // todavía bloqueada
	}

	// 2. Buscar la última fecha de expiración (aunque ya esté vencida)
	$stmt = $pdo->prepare("
        SELECT MAX(fecha_expiracion) 
        FROM sec_ip_bloqueadas 
        WHERE ip = :ip
    ");
	$stmt->execute(['ip' => $ip]);
	$ultimaExpiracion = $stmt->fetchColumn();

	// 3. Contar intentos fallidos SOLO después de la última expiración
	$sql = "
        SELECT COUNT(*) 
        FROM sec_intentos_login 
        WHERE ip = :ip 
          AND exito = 0 
          AND fecha > DATE_SUB(NOW(), INTERVAL :min MINUTE)
    ";

	// Si hubo bloqueos antes, contar desde la última expiración en adelante
	if ($ultimaExpiracion) {
		$sql .= " AND fecha > :ultimaExpiracion";
	}

	$stmt = $pdo->prepare($sql);

	$params = [
		'ip' => $ip,
		'min' => $ventanaMinutos
	];
	
	if ($ultimaExpiracion) {
		$params['ultimaExpiracion'] = $ultimaExpiracion;
	}

	$stmt->execute($params);
	$fallos = $stmt->fetchColumn();

	// 4. Si superó el límite, bloquear de nuevo
	if ($fallos >= $maxIntentos) {
		bloquear_ip($pdo, $ip, $minutosBloqueo);
		return ip_bloqueada($pdo, $ip);
	}

	return false;
}
