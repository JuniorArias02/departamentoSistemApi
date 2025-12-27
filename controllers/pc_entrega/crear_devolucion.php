<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(["error" => "Método no permitido"]);
	exit;
}

$entregaId     = $_POST['entrega_id'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;

if (!$entregaId) {
	http_response_code(400);
	echo json_encode(["error" => "ID de entrega requerido"]);
	exit;
}

/* ================== DIRECTORIO ================== */
$directorio = __DIR__ . '/../../public/entrega_activos/';
$ext_permitidas = ['png'];

if (!file_exists($directorio)) {
	mkdir($directorio, 0755, true);
}

/* ================== FUNCION ================== */
function guardarFirma($campo, $file, $directorio, $ext_permitidas)
{
	if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
		http_response_code(400);
		echo json_encode(["error" => "Firma $campo requerida"]);
		exit;
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

/* ================== GUARDAR FIRMAS ================== */
$firmaEntrega = guardarFirma('firma_entrega', 'firma_entrega', $directorio, $ext_permitidas);
$firmaRecibe  = guardarFirma('firma_recibe', 'firma_recibe', $directorio, $ext_permitidas);

/* ================== TRANSACCION ================== */
try {
	$pdo->beginTransaction();

	// insertar devolucion
	$sql = "
		INSERT INTO pc_devuelto 
		(entrega_id, fecha_devolucion, firma_entrega, firma_recibe, observaciones)
		VALUES (:entrega_id, NOW(), :firma_entrega, :firma_recibe, :observaciones)
	";

	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		":entrega_id"     => $entregaId,
		":firma_entrega"  => $firmaEntrega,
		":firma_recibe"   => $firmaRecibe,
		":observaciones"  => $observaciones
	]);

	// actualizar entrega
	$sqlUpdate = "
		UPDATE pc_entregas
		SET 
			devuelto = CURDATE(),
			estado = 'devuelto'
		WHERE id = :id
	";

	$stmtUpdate = $pdo->prepare($sqlUpdate);
	$stmtUpdate->execute([":id" => $entregaId]);

	$pdo->commit();

	echo json_encode([
		"ok" => true,
		"message" => "Equipo devuelto correctamente"
	]);

} catch (Exception $e) {
	$pdo->rollBack();
	http_response_code(500);
	echo json_encode(["error" => "Error al devolver equipo"]);
}
