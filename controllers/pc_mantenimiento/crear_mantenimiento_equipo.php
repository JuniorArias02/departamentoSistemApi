<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

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
    "equipo_id",
    "tipo_mantenimiento",
    "descripcion",
    "fecha",
    "empresa_responsable_id",
    "repuesto",
    "cantidad_repuesto",
    "costo_repuesto",
    "nombre_repuesto",
    "responsable_mantenimiento",
    "firma_personal_cargo",
    "firma_sistemas"
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
    $stmt = $pdo->prepare("
        INSERT INTO pc_mantenimientos (
            equipo_id, tipo_mantenimiento, descripcion, fecha,
            empresa_responsable_id, repuesto, cantidad_repuesto,
            costo_repuesto, nombre_repuesto, responsable_mantenimiento,
            firma_personal_cargo, firma_sistemas
        ) VALUES (
            :equipo_id, :tipo_mantenimiento, :descripcion, :fecha,
            :empresa_responsable_id, :repuesto, :cantidad_repuesto,
            :costo_repuesto, :nombre_repuesto, :responsable_mantenimiento,
            :firma_personal_cargo, :firma_sistemas
        )
    ");

    $stmt->execute([
        "equipo_id"               => $data["equipo_id"],
        "tipo_mantenimiento"      => $data["tipo_mantenimiento"],
        "descripcion"             => $data["descripcion"],
        "fecha"                   => $data["fecha"],
        "empresa_responsable_id"  => $data["empresa_responsable_id"],
        "repuesto"                => $data["repuesto"],
        "cantidad_repuesto"       => $data["cantidad_repuesto"],
        "costo_repuesto"          => $data["costo_repuesto"],
        "nombre_repuesto"         => $data["nombre_repuesto"],
        "responsable_mantenimiento" => $data["responsable_mantenimiento"],
        "firma_personal_cargo"    => $data["firma_personal_cargo"],
        "firma_sistemas"          => $data["firma_sistemas"]
    ]);

    $mantenimiento_id = $pdo->lastInsertId();

    registrarActividad($pdo, $usuario_id, "Registro", "pc_mantenimientos", $mantenimiento_id);

    echo json_encode([
        "status" => true,
        "message" => "Mantenimiento registrado correctamente"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al registrar mantenimiento: " . $e->getMessage()
    ]);
}
