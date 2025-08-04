<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

$usuario_id = $data["usuario_id"] ?? null;
$id = $data["id"] ?? null; 

if (!$usuario_id || !$id) {
    echo json_encode([
        "status" => false,
        "message" => "No tienes permisos o falta el ID del mantenimiento"
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
        UPDATE pc_mantenimientos SET
            equipo_id = :equipo_id,
            tipo_mantenimiento = :tipo_mantenimiento,
            descripcion = :descripcion,
            fecha = :fecha,
            empresa_responsable_id = :empresa_responsable_id,
            repuesto = :repuesto,
            cantidad_repuesto = :cantidad_repuesto,
            costo_repuesto = :costo_repuesto,
            nombre_repuesto = :nombre_repuesto,
            responsable_mantenimiento = :responsable_mantenimiento,
            firma_personal_cargo = :firma_personal_cargo,
            firma_sistemas = :firma_sistemas
        WHERE id = :id
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
        "firma_sistemas"          => $data["firma_sistemas"],
        "id"                      => $id
    ]);

    registrarActividad($pdo, $usuario_id, "Actualización", "pc_mantenimientos", $id);

    echo json_encode([
        "status" => true,
        "message" => "Mantenimiento actualizado correctamente"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al actualizar mantenimiento: " . $e->getMessage()
    ]);
}
