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
    "responsable_mantenimiento"
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
            creado_por, fecha_creacion
        ) VALUES (
            :equipo_id, :tipo_mantenimiento, :descripcion, :fecha,
            :empresa_responsable_id, :repuesto, :cantidad_repuesto,
            :costo_repuesto, :nombre_repuesto, :responsable_mantenimiento,
            :creado_por, NOW()
        )
    ");

    $stmt->execute([
        "equipo_id"                 => $data["equipo_id"],
        "tipo_mantenimiento"        => $data["tipo_mantenimiento"],
        "descripcion"               => $data["descripcion"],
        "fecha"                     => $data["fecha"],
        "empresa_responsable_id"    => $data["empresa_responsable_id"],
        "repuesto"                  => !empty($data["repuesto"]) ? 1 : 0,
        "cantidad_repuesto"         => ($data["cantidad_repuesto"] === "" || $data["cantidad_repuesto"] === null) ? 0 : (int)$data["cantidad_repuesto"],
        "costo_repuesto"            => ($data["costo_repuesto"] === "" || $data["costo_repuesto"] === null) ? 0 : (int)$data["costo_repuesto"],
        "nombre_repuesto"           => $data["nombre_repuesto"] ?: null,
        "responsable_mantenimiento" => $data["responsable_mantenimiento"],
        "creado_por"                => $usuario_id
    ]);


    $mantenimiento_id = $pdo->lastInsertId();

    registrarActividad($pdo, $usuario_id, "Registro", "pc_mantenimientos", $mantenimiento_id);

    echo json_encode([
        "status" => true,
        "message" => "Mantenimiento registrado correctamente",
        "mantenimiento_id" => $mantenimiento_id
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al registrar mantenimiento: " . $e->getMessage()
    ]);
}
