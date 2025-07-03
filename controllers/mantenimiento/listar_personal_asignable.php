<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Validación del usuario que hace la petición
$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $data['usuario_id'] ?? null;

if (!$usuario_id || !tienePermiso($pdo, $usuario_id, PERMISOS['MANTENIMIENTOS']['CREAR'])) {
	http_response_code(403);
	echo json_encode([
		"success" => false,
		"message" => "No tienes permiso para acceder a este recurso."
	]);
	exit();
}

try {
	$stmt = $pdo->prepare("
        SELECT u.id, u.nombre_completo, u.correo
        FROM usuarios u
        JOIN rol_permisos rp ON rp.rol_id = u.rol_id
        JOIN permisos p ON p.id = rp.permiso_id
        WHERE p.nombre = :permiso
          AND u.estado = 1
        GROUP BY u.id
    ");
	$stmt->execute([
		'permiso' => PERMISOS['MANTENIMIENTOS']['RECIBIR_MANTENIMIENTO']
	]);

	$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($usuarios);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"message" => "Error al obtener usuarios asignables.",
		"error" => $e->getMessage()
	]);
}
