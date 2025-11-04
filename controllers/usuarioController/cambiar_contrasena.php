<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../sec_codigo_verificacion/usar_codigo.php';
require_once __DIR__ . '/../sec_contrasenas_anteriores/guardar_contrasena_anterior.php';


header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$usuario_input = $data['usuario'] ?? null;
$codigo = $data['codigo'] ?? null;
$nueva_contrasena = $data['nueva_contrasena'] ?? null;

if (!$usuario_input || !$codigo || !$nueva_contrasena) {
	echo json_encode(['status' => false, 'message' => 'Faltan datos requeridos']);
	exit;
}

try {
	// Validar que el código siga vigente
	if (!usar_codigo($pdo, $usuario_input, $codigo)) {
		echo json_encode(['status' => false, 'message' => 'Código usado o expirado']);
		exit;
	}

	// Buscar usuario
	$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1");
	$stmt->bindParam(':usuario', $usuario_input, PDO::PARAM_STR);
	$stmt->execute();
	$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$usuario) {
		echo json_encode(['status' => false, 'message' => 'Usuario no encontrado']);
		exit;
	}

	// Hashear nueva contraseña
	$hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);


	// Antes de actualizar la contraseña
	$stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id = :id LIMIT 1");
	$stmt->bindParam(':id', $usuario['id'], PDO::PARAM_INT);
	$stmt->execute();
	$actual = $stmt->fetchColumn();

	// Guardar la contraseña anterior
	guardar_contrasena_anterior($pdo, $usuario['id'], $actual);

	// Ahora sí actualizar la contraseña
	$hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
	$stmt = $pdo->prepare("UPDATE usuarios SET contrasena = :contrasena WHERE id = :id");
	$stmt->bindParam(':contrasena', $hash, PDO::PARAM_STR);
	$stmt->bindParam(':id', $usuario['id'], PDO::PARAM_INT);
	$stmt->execute();


	echo json_encode(['status' => true, 'message' => 'Contraseña actualizada correctamente']);
} catch (PDOException $e) {
	echo json_encode(['status' => false, 'message' => 'Error en la base de datos', 'error' => $e->getMessage()]);
}
