<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$equipo_id = $_POST["equipo_id"] ?? null;

if (!$equipo_id || !isset($_FILES["imagen"])) {
	echo json_encode([
		"status" => false,
		"message" => "Faltan datos necesarios"
	]);
	exit;
}

$carpetaDestino = __DIR__ . '/../../public/equipos/';
$rutaRelativa = 'equipos/';
$nombreArchivo = null;

if (!file_exists($carpetaDestino)) {
	mkdir($carpetaDestino, 0777, true);
}

if ($_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
	$nombreOriginal = $_FILES['imagen']['name'];
	$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

	if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
		echo json_encode([
			"status" => false,
			"message" => "Tipo de imagen no permitido"
		]);
		exit;
	}

	$nombreArchivo = uniqid('img_') . '.' . $extension;
	move_uploaded_file($_FILES['imagen']['tmp_name'], $carpetaDestino . $nombreArchivo);

	// Guardar en la BD
	$stmt = $pdo->prepare("UPDATE pc_equipos SET imagen_url = :imagen_url WHERE id = :id");
	$stmt->execute([
		"id" => $equipo_id,
		"imagen_url" => $rutaRelativa . $nombreArchivo
	]);

	echo json_encode([
		"status" => true,
		"message" => "Imagen subida correctamente"
	]);
} else {
	echo json_encode([
		"status" => false,
		"message" => "Error al subir la imagen"
	]);
}
