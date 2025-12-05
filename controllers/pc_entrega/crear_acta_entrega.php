<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/funciones_pc_entregas.php';

$input = json_decode(file_get_contents("php://input"), true);
$usuario_id = $input["usuario_id"] ?? null;

if (!$usuario_id) {
	echo json_encode(["status" => false, "message" => "No tienes permisos para realizar esta acción"]);
	exit;
}
$requeridos = ["equipo_id", "funcionario_id", "fecha_entrega"];
$faltantes = validarCampos($input, $requeridos);
if (!empty($faltantes)) {
	echo json_encode(["status" => false, "message" => "Campos requeridos faltantes", "faltantes" => $faltantes]);
	exit;
}

try {
	$pdo->beginTransaction();

	// Firmas opcionales
	$firma_entrega = !empty($input["firma_entrega"])
		? guardarFirmaBase64($input["firma_entrega"], "entrega")
		: null;

	$firma_recibe = !empty($input["firma_recibe"])
		? guardarFirmaBase64($input["firma_recibe"], "recibe")
		: null;

	if ($firma_entrega === false || $firma_recibe === false) {
		throw new Exception("Error al guardar las firmas");
	}

	$entrega_id = insertarActa($pdo, $input, $firma_entrega, $firma_recibe);
	actualizarResponsableEquipo($pdo, $input);
	insertarPerifericos($pdo, $entrega_id, $input["perifericos"] ?? []);

	registrarActividad($pdo, $usuario_id, "Registro", "pc_entregas", $entrega_id);

	$pdo->commit();

	echo json_encode(["status" => true, "message" => "Acta registrada con éxito"]);
} catch (Exception $e) {
	$pdo->rollBack();
	echo json_encode(["status" => false, "message" => "Error: " . $e->getMessage()]);
}
