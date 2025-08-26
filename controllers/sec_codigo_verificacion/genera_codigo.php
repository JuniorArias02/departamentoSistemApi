<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../sec_auditoria/registrar_accion.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoCodigoRecuperacion.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$usuario_input = $data['usuario'] ?? null;

if (!$usuario_input) {
	echo json_encode(['status' => false, 'message' => 'Falta el nombre de usuario']);
	exit;
}

try {
	// Buscar usuario por nombre
	$stmt = $pdo->prepare("SELECT id, nombre_completo, correo FROM usuarios WHERE usuario = :usuario LIMIT 1");
	$stmt->bindParam(':usuario', $usuario_input, PDO::PARAM_STR);
	$stmt->execute();
	$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$usuario) {
		echo json_encode(['status' => false, 'message' => 'Usuario no encontrado']);
		exit;
	}

	// Generar código de 6 dígitos
	$codigo = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

	// Insertar código en la tabla sec_codigo_verificacion
	$stmt = $pdo->prepare("
        INSERT INTO sec_codigo_verificacion 
        (codigo, id_usuario, creado, fecha_activacion, fecha_expiracion) 
        VALUES (:codigo, :id_usuario, NOW(), NULL, NULL)
    ");
	$stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
	$stmt->bindParam(':id_usuario', $usuario['id'], PDO::PARAM_INT);
	$stmt->execute();

	// Enviar correo
	$correo_enviado = enviarCorreoCodigoRecuperacion($usuario['correo'], $usuario['nombre_completo'], $codigo);

	if (!$correo_enviado) {
		echo json_encode([
			'status' => false,
			'message' => 'Código generado, pero no se pudo enviar el correo'
		]);
		exit;
	}

	$ip = $_SERVER['REMOTE_ADDR'];
	registrar_accion($pdo, $usuario['id'], 'GENERA_CODIGO_RECUPERACION', "Código de recuperación generado y enviado", $ip);


	echo json_encode([
		'status' => true,
		'message' => 'Código generado y enviado correctamente',
		//'codigo' => $codigo // opcional, no mostrar en producción
	]);
} catch (PDOException $e) {
	echo json_encode(['status' => false, 'message' => 'Error en la base de datos', 'error' => $e->getMessage()]);
}
