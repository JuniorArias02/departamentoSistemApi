<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

if (empty($_POST['id'])) {
	http_response_code(400);
	echo json_encode(["error" => "ID obligatorio"]);
	exit;
}

$id = (int) $_POST['id'];
$ext_permitidas = ['png'];
$directorio = __DIR__ . '/../../public/entrega_activos/';

if (!is_dir($directorio)) {
	mkdir($directorio, 0755, true);
}

function guardarFirma($input, $prefijo, $directorio, $exts) {
	if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
		return null;
	}

	$ext = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));
	if (!in_array($ext, $exts)) {
		http_response_code(400);
		echo json_encode(["error" => "Solo PNG permitido"]);
		exit;
	}

	$nombre = uniqid($prefijo . "_") . ".png";
	$rutaAbs = $directorio . $nombre;
	$rutaRel = "public/entrega_activos/" . $nombre;

	if (!move_uploaded_file($_FILES[$input]['tmp_name'], $rutaAbs)) {
		http_response_code(500);
		echo json_encode(["error" => "Error guardando firma"]);
		exit;
	}

	return $rutaRel;
}

/* Obtener firmas actuales */
$stmt = $pdo->prepare("SELECT firma_entrega, firma_recibe FROM pc_entregas WHERE id = ?");
$stmt->execute([$id]);
$actual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actual) {
	http_response_code(404);
	echo json_encode(["error" => "Entrega no encontrada"]);
	exit;
}

/* Procesar nuevas firmas */
$firmaEntrega = guardarFirma('firma_entrega', 'entrega', $directorio, $ext_permitidas) ?? $actual['firma_entrega'];
$firmaRecibe  = guardarFirma('firma_recibe', 'recibe', $directorio, $ext_permitidas) ?? $actual['firma_recibe'];

try {
	$stmt = $pdo->prepare("
		UPDATE pc_entregas 
		SET firma_entrega = :entrega,
		    firma_recibe  = :recibe
		WHERE id = :id
	");

	$stmt->execute([
		':entrega' => $firmaEntrega,
		':recibe'  => $firmaRecibe,
		':id'      => $id
	]);

	echo json_encode([
		"success" => true,
		"firma_entrega" => $firmaEntrega,
		"firma_recibe"  => $firmaRecibe
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => $e->getMessage()]);
}
