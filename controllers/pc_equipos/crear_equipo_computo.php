<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Leer JSON desde el body
$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $data["usuario_id"] ?? null;

if (!$usuario_id) {
	echo json_encode([
		"status" => false,
		"message" => "No tienes permisos para realizar esta acción"
	]);
	exit;
}

$requeridos = [
	"nombre_equipo",
	"marca",
	"modelo",
	"serial",
	"tipo",
	"propiedad",
	"ip_fija",
	"numero_inventario",
	"sede_id",
	"area_id",
	"responsable_id",
	"estado",
	"fecha_entrega",
	"descripcion_general",
	"garantia_meses",
	"forma_adquisicion",
	"procesador",
	"memoria_ram",
	"disco_duro",
	"fecha_ingreso"
];

$faltantes = [];

foreach ($requeridos as $campo) {
	if (!isset($data[$campo]) || $data[$campo] === "") {
		$faltantes[] = $campo;
	}
}

if (!empty($faltantes)) {
	echo json_encode([
		"status" => false,
		"message" => "Campos requeridos faltantes",
		"faltantes" => $faltantes
	]);
	exit;
}

try {
	$pdo->beginTransaction();

	// Insertar equipo
	$stmt = $pdo->prepare("
		INSERT INTO pc_equipos (
			nombre_equipo, marca, modelo, serial, tipo, propiedad, ip_fija,
			numero_inventario, sede_id, area_id, responsable_id, estado,
			fecha_entrega, descripcion_general, garantia_meses, forma_adquisicion, observaciones,
			fecha_ingreso, repuestos_principales, recomendaciones , equipos_adicionales
		) VALUES (
			:nombre_equipo, :marca, :modelo, :serial, :tipo, :propiedad, :ip_fija,
			:numero_inventario, :sede_id, :area_id, :responsable_id, :estado,
			:fecha_entrega, :descripcion_general, :garantia_meses, :forma_adquisicion, :observaciones,
			:fecha_ingreso, :repuestos_principales, :recomendaciones, :equipos_adicionales
		)
	");

	$stmt->execute([
		"nombre_equipo"       => $data["nombre_equipo"],
		"marca"               => $data["marca"],
		"modelo"              => $data["modelo"],
		"serial"              => $data["serial"],
		"tipo"                => $data["tipo"],
		"propiedad"           => $data["propiedad"],
		"ip_fija"             => $data["ip_fija"],
		"numero_inventario"   => $data["numero_inventario"],
		"sede_id"             => $data["sede_id"],
		"area_id"             => $data["area_id"],
		"responsable_id"      => $data["responsable_id"],
		"estado"              => $data["estado"],
		"fecha_entrega"       => $data["fecha_entrega"],
		"descripcion_general" => $data["descripcion_general"],
		"garantia_meses"      => $data["garantia_meses"],
		"forma_adquisicion"   => $data["forma_adquisicion"],
		"observaciones"       => $data["observaciones"] ?? null,
		"fecha_ingreso"       => $data["fecha_ingreso"],
		"repuestos_principales" => $data["repuestos_principales"] ?? null,
		"recomendaciones"     => $data["recomendaciones"] ?? null,
		"equipos_adicionales" => $data["equipos_adicionales"] ?? null
	]);

	$equipo_id = $pdo->lastInsertId();

	// Insertar características técnicas
	$stmt = $pdo->prepare("
		INSERT INTO pc_caracteristicas_tecnicas (
			equipo_id, procesador, memoria_ram, disco_duro, tarjeta_video,
			tarjeta_red, tarjeta_sonido, usb, unidad_cd, parlantes, drive,
			monitor, teclado, mouse, internet, velocidad_red, capacidad_disco
		) VALUES (
			:equipo_id, :procesador, :memoria_ram, :disco_duro, :tarjeta_video,
			:tarjeta_red, :tarjeta_sonido, :usb, :unidad_cd, :parlantes, :drive,
			:monitor, :teclado, :mouse, :internet, :velocidad_red, :capacidad_disco
		)
	");
	$stmt->execute([
		"equipo_id"       => $equipo_id,
		"procesador"      => $data["procesador"],
		"memoria_ram"     => $data["memoria_ram"],
		"disco_duro"      => $data["disco_duro"],
		"tarjeta_video"   => $data["tarjeta_video"] ?? null,
		"tarjeta_red"     => $data["tarjeta_red"] ?? null,
		"tarjeta_sonido"  => $data["tarjeta_sonido"] ?? null,
		"usb"             => $data["usb"] ?? null,
		"unidad_cd"       => $data["unidad_cd"] ?? null,
		"parlantes"       => $data["parlantes"] ?? null,
		"drive"           => $data["drive"] ?? null,
		"monitor"         => $data["monitor"] ?? null,
		"teclado"         => $data["teclado"] ?? null,
		"mouse"           => $data["mouse"] ?? null,
		"internet"        => $data["internet"] ?? null,
		"velocidad_red"   => $data["velocidad_red"] ?? null,
		"capacidad_disco" => $data["capacidad_disco"] ?? null
	]);

	// Insertar licencias
	$stmt = $pdo->prepare("
		INSERT INTO pc_licencias_software (
			equipo_id, windows, office, nitro
		) VALUES (
			:equipo_id, :windows, :office, :nitro
		)
	");
	$stmt->execute([
		"equipo_id" => $equipo_id,
		"windows"   => $data["windows"] ?? 0,
		"office"    => $data["office"] ?? 0,
		"nitro"     => $data["nitro"] ?? 0
	]);

	// Registrar actividad
	if ($usuario_id) {
		registrarActividad($pdo, $usuario_id, "Registro", "pc_equipos", $equipo_id);
	}

	$pdo->commit();

	echo json_encode([
		"status" => true,
		"message" => "Equipo creado correctamente"
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		"status" => false,
		"message" => "Error al crear el equipo: " . $e->getMessage()
	]);
}
