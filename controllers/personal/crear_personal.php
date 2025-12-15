<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

$data = json_decode(file_get_contents("php://input"), true);

$usuario_id = $data["usuario_id"] ?? null;

if (!$usuario_id) {
    echo json_encode([
        "status" => false,
        "message" => "No tienes permisos para realizar esta acción"
    ]);
    exit;
}

// Campos requeridos
$requeridos = ["nombre", "cedula", "telefono", "cargo_id"];
$faltantes = [];

foreach ($requeridos as $campo) {
    if (empty($data[$campo])) {
        $faltantes[] = $campo;
    }
}

if ($faltantes) {
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
            nombre, cedula, telefono, cargo_id
        ) VALUES (
            :nombre, :cedula, :telefono, :cargo_id
        )
    ");

    $stmt->execute([
        "nombre"   => $data["nombre"],
        "cedula"   => $data["cedula"],
        "telefono" => $data["telefono"],
        "cargo_id" => $data["cargo_id"]
    ]);

    $nuevo_id = $pdo->lastInsertId();

    registrarActividad($pdo, $usuario_id, "Registro", "personal", $nuevo_id);

    echo json_encode([
        "status" => true,
        "message" => "Personal registrado correctamente",
        "id" => $nuevo_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al registrar el personal"
    ]);
}
