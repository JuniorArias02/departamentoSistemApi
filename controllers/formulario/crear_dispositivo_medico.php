<?php
require_once '../../database/conexion.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$camposObligatorios = [
  "descripcion",
  "marca",
  "serie",
  "presentacion_comercial",
  "registro_sanitario",
  "clasificacion_riesgo",
  "vida_util",
  "lote",
  "fecha_vencimiento",
  "creado_por"
];

foreach ($camposObligatorios as $campo) {
  if (!isset($data[$campo]) || trim($data[$campo]) === "") {
    http_response_code(400);
    echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
    exit;
  }
}

// Verifica si se va a actualizar o insertar
if (isset($data["id"]) && is_numeric($data["id"])) {
  // ACTUALIZAR
  $stmt = $pdo->prepare("UPDATE dispositivos_medicos SET
    descripcion = :descripcion,
    marca = :marca,
    serie = :serie,
    presentacion_comercial = :presentacion_comercial,
    registro_sanitario = :registro_sanitario,
    clasificacion_riesgo = :clasificacion_riesgo,
    vida_util = :vida_util,
    lote = :lote,
    fecha_vencimiento = :fecha_vencimiento,
    creado_por = :creado_por
    WHERE id = :id
  ");

  $stmt->execute([
    "descripcion" => $data["descripcion"],
    "marca" => $data["marca"],
    "serie" => $data["serie"],
    "presentacion_comercial" => $data["presentacion_comercial"],
    "registro_sanitario" => $data["registro_sanitario"],
    "clasificacion_riesgo" => $data["clasificacion_riesgo"],
    "vida_util" => $data["vida_util"],
    "lote" => $data["lote"],
    "fecha_vencimiento" => $data["fecha_vencimiento"],
    "creado_por" => $data["creado_por"],
    "id" => $data["id"],
  ]);

  echo json_encode(["msg" => "Dispositivo actualizado con éxito"]);
} else {
  // INSERTAR
  $stmt = $pdo->prepare("INSERT INTO dispositivos_medicos 
    (descripcion, marca, serie, presentacion_comercial, registro_sanitario, clasificacion_riesgo, vida_util, lote, fecha_vencimiento, creado_por)
    VALUES 
    (:descripcion, :marca, :serie, :presentacion_comercial, :registro_sanitario, :clasificacion_riesgo, :vida_util, :lote, :fecha_vencimiento, :creado_por)
  ");

  $stmt->execute([
    "descripcion" => $data["descripcion"],
    "marca" => $data["marca"],
    "serie" => $data["serie"],
    "presentacion_comercial" => $data["presentacion_comercial"],
    "registro_sanitario" => $data["registro_sanitario"],
    "clasificacion_riesgo" => $data["clasificacion_riesgo"],
    "vida_util" => $data["vida_util"],
    "lote" => $data["lote"],
    "fecha_vencimiento" => $data["fecha_vencimiento"],
    "creado_por" => $data["creado_por"],
  ]);

  echo json_encode(["msg" => "Dispositivo registrado con éxito"]);
}
