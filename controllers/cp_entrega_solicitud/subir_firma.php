<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Validar campos obligatorios
if (!isset($_POST['consecutivo_id']) || empty($_POST['consecutivo_id'])) {
	http_response_code(400);
	echo json_encode(["error" => "El consecutivo_id es obligatorio"]);
	exit;
}

if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
	http_response_code(400);
	echo json_encode(["error" => "El usuario_id es obligatorio"]);
	exit;
}

if (!isset($_FILES['firma']) || $_FILES['firma']['error'] !== UPLOAD_ERR_OK) {
	http_response_code(400);
	echo json_encode(["error" => "La firma es obligatoria"]);
	exit;
}

$consecutivoId = intval($_POST['consecutivo_id']);
$usuarioId     = intval($_POST['usuario_id']);

// Validar permiso (el mismo de crear entrega puede aplicar para subir firma)
if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['CREAR_ENTREGA_SOLICITUD'])) {
	http_response_code(403);
	echo json_encode(["error" => "No tienes permiso para subir firmas"]);
	exit;
}

// Validar extensión
$ext_permitidas = ['png'];
$extension = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $ext_permitidas)) {
	http_response_code(400);
	echo json_encode(["error" => "Solo se permite formato PNG"]);
	exit;
}

// Carpeta destino
$directorio = __DIR__ . '/../../public/entrega_pedido/';
if (!file_exists($directorio)) {
	mkdir($directorio, 0755, true);
}

$nombre_archivo = uniqid("firma_entrega_") . ".png";
$ruta_absoluta  = $directorio . $nombre_archivo;
$ruta_relativa  = "public/entrega_pedido/" . $nombre_archivo;

// Guardar archivo
if (!move_uploaded_file($_FILES['firma']['tmp_name'], $ruta_absoluta)) {
	http_response_code(500);
	echo json_encode(["error" => "No se pudo guardar la firma"]);
	exit;
}

try {
	// Actualizar registro
	$sql = "UPDATE cp_entrega_solicitud 
            SET firma_quien_recibe = :firma, 
                estado = 1
            WHERE consecutivo_id = :id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		':firma' => $ruta_relativa,
		':id'    => $consecutivoId
	]);

	echo json_encode([
		"success" => true,
		"message" => "Firma subida y estado actualizado a 1 correctamente",
		"ruta"    => $ruta_relativa
	]);

} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"error" => "Error al guardar la firma: " . $e->getMessage()
	]);
}
