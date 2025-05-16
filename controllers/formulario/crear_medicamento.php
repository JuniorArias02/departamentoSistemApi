<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../database/conexion.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$campos = [
  "principio_activo", "forma_farmaceutica", "concentracion", "lote",
  "fecha_vencimiento", "presentacion_comercial", "unidad_medida",
  "registro_sanitario", "creado_por"
];

foreach ($campos as $campo) {
  if (!isset($data[$campo]) || trim($data[$campo]) === "") {
    http_response_code(400);
    echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
    exit;
  }
}

try {
    if (!empty($data['id'])) {
        // EDITAR medicamento existente
        $stmt = $pdo->prepare("UPDATE medicamentos SET 
            principio_activo = :principio_activo,
            forma_farmaceutica = :forma_farmaceutica,
            concentracion = :concentracion,
            lote = :lote,
            fecha_vencimiento = :fecha_vencimiento,
            presentacion_comercial = :presentacion_comercial,
            unidad_medida = :unidad_medida,
            registro_sanitario = :registro_sanitario
            WHERE id = :id");

        $stmt->execute([
            "principio_activo" => $data["principio_activo"],
            "forma_farmaceutica" => $data["forma_farmaceutica"],
            "concentracion" => $data["concentracion"],
            "lote" => $data["lote"],
            "fecha_vencimiento" => $data["fecha_vencimiento"],
            "presentacion_comercial" => $data["presentacion_comercial"],
            "unidad_medida" => $data["unidad_medida"],
            "registro_sanitario" => $data["registro_sanitario"],
            "id" => $data["id"]
        ]);

        echo json_encode(["msg" => "Medicamento actualizado con éxito"]);
    } else {
        // CREAR nuevo medicamento
        $stmt = $pdo->prepare("INSERT INTO medicamentos 
            (principio_activo, forma_farmaceutica, concentracion, lote, fecha_vencimiento, presentacion_comercial, unidad_medida, registro_sanitario, creado_por) 
            VALUES (:principio_activo, :forma_farmaceutica, :concentracion, :lote, :fecha_vencimiento, :presentacion_comercial, :unidad_medida, :registro_sanitario, :creado_por)");

        $stmt->execute([
            "principio_activo" => $data["principio_activo"],
            "forma_farmaceutica" => $data["forma_farmaceutica"],
            "concentracion" => $data["concentracion"],
            "lote" => $data["lote"],
            "fecha_vencimiento" => $data["fecha_vencimiento"],
            "presentacion_comercial" => $data["presentacion_comercial"],
            "unidad_medida" => $data["unidad_medida"],
            "registro_sanitario" => $data["registro_sanitario"],
            "creado_por" => $data["creado_por"],
        ]);

        echo json_encode(["msg" => "Medicamento registrado con éxito"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el medicamento: " . $e->getMessage()]);
}
