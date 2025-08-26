<?php
function usar_codigo($pdo, $usuario_input, $codigo)
{
	date_default_timezone_set('America/Bogota');

	// Buscar el código del usuario
	$stmt = $pdo->prepare("
        SELECT id, fecha_expiracion, consumido
        FROM sec_codigo_verificacion
        WHERE codigo = :codigo
        AND id_usuario = (SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1)
        LIMIT 1
    ");
	$stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
	$stmt->bindParam(':usuario', $usuario_input, PDO::PARAM_STR);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) return false; // Código no encontrado

	$ahora = new DateTime();
	$expiracion = new DateTime($row['fecha_expiracion']);

	// Verificar expiración
	if ($ahora > $expiracion) return false;

	// Verificar si ya fue consumido
	if ($row['consumido'] == 1) return false;

	// Marcar como consumido
	$stmt = $pdo->prepare("
        UPDATE sec_codigo_verificacion 
        SET consumido = 1 
        WHERE id = :id
    ");
	$stmt->bindParam(':id', $row['id'], PDO::PARAM_INT);
	$stmt->execute();

	return true;
}
