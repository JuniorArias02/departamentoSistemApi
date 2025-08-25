<?php
require_once __DIR__ . '/../../database/conexion.php';
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

// Validar campos requeridos
$requeridos = ["nombre"];
$faltantes = [];

foreach ($requeridos as $campo) {
    if (!isset($data[$campo]) || trim($data[$campo]) === "") {
        $faltantes[] = $campo;
    }
}

if (!empty($faltantes)) {
    echo json_encode([
        "status" => false,
        "message" => "Campos faltantes",
        "faltantes" => $faltantes
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO personal (
            nombre, cedula, telefono, cargo, proceso
        ) VALUES (
            :nombre, :cedula, :telefono, :cargo, :proceso
        )
    ");

    $stmt->execute([
        "nombre"   => $data["nombre"],
        "cedula"   => $data["cedula"],
        "telefono" => $data["telefono"],
        "cargo"    => $data["cargo"],
        "proceso"  => $data["proceso"]
    ]);

    $nuevo_id = $pdo->lastInsertId();

    registrarActividad($pdo, $usuario_id, "Registro", "personal", $nuevo_id);

    echo json_encode([
        "status" => true,
        "message" => "Personal registrado correctamente"
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al registrar el personal: " . $e->getMessage()
    ]);
}
