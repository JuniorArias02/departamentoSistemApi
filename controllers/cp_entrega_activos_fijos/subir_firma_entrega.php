<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

if (!isset($_POST['id']) || empty($_POST['id'])) {
	http_response_code(400);
	echo json_encode(["error" => "El id de la entrega es obligatorio"]);
	exit;
}

$id = intval($_POST['id']);
$ext_permitidas = ['png'];
$directorio = __DIR__ . '/../../public/entrega_activos/';

if (!file_exists($directorio)) {
	mkdir($directorio, 0755, true);
}

function guardarFirma($campo, $file, $directorio, $ext_permitidas)
{
	if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
		return false;
	}

	$extension = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
	if (!in_array($extension, $ext_permitidas)) {
		http_response_code(400);
		echo json_encode(["error" => "Solo se permite formato PNG"]);
		exit;
	}

	$nombre_archivo = uniqid("{$campo}_") . ".png";
	$ruta_absoluta  = $directorio . $nombre_archivo;
	$ruta_relativa  = "public/entrega_activos/" . $nombre_archivo;

	if (!move_uploaded_file($_FILES[$file]['tmp_name'], $ruta_absoluta)) {
		http_response_code(500);
		echo json_encode(["error" => "No se pudo guardar la firma de $campo"]);
		exit;
	}

	return $ruta_relativa;
}

// --- NUEVO: obtener las firmas actuales ---
$stmt = $pdo->prepare("SELECT firma_quien_entrega, firma_quien_recibe FROM cp_entrega_activos_fijos WHERE id = ?");
$stmt->execute([$id]);
$firmaActual = $stmt->fetch(PDO::FETCH_ASSOC);

// --- procesar nuevas ---
$firmaEntrega = guardarFirma("firma_entrega", "firma_entrega", $directorio, $ext_permitidas);
$firmaRecibe  = guardarFirma("firma_recibe", "firma_recibe", $directorio, $ext_permitidas);

// --- mantener las existentes si no llegan nuevas ---
if ($firmaEntrega === false && !empty($firmaActual['firma_quien_entrega'])) {
	$firmaEntrega = $firmaActual['firma_quien_entrega'];
}
if ($firmaRecibe === false && !empty($firmaActual['firma_quien_recibe'])) {
	$firmaRecibe = $firmaActual['firma_quien_recibe'];
}

try {
	$sql = "UPDATE cp_entrega_activos_fijos 
			SET firma_quien_entrega = :firma_entrega, 
			    firma_quien_recibe = :firma_recibe 
			WHERE id = :id";

	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		':firma_entrega' => $firmaEntrega,
		':firma_recibe'  => $firmaRecibe,
		':id'            => $id
	]);

	echo json_encode([
		"success" => true,
		"message" => "Firmas guardadas correctamente",
		"ruta_entrega" => $firmaEntrega,
		"ruta_recibe"  => $firmaRecibe
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al guardar firmas: " . $e->getMessage()]);
}
