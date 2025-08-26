
<?php

function registrar_intento_login($pdo, $usuario_input, $id_usuario = null, $exito = 0)
{
	$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	$stmt = $pdo->prepare("
INSERT INTO sec_intentos_login (id_usuario, usuario_ingresado, ip, exito, fecha)
VALUES (:id_usuario, :usuario_ingresado, :ip, :exito, NOW())
");
	$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
	$stmt->bindParam(':usuario_ingresado', $usuario_input, PDO::PARAM_STR);
	$stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
	$stmt->bindParam(':exito', $exito, PDO::PARAM_INT);
	$stmt->execute();
}
