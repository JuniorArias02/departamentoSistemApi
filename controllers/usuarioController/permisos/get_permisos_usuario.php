<?php
require_once '../../../database/conexion.php';
require_once __DIR__ . '/../../../middlewares/headers_post.php';


// Obtener los datos del body
$input = json_decode(file_get_contents("php://input"), true);

// Validar el ID
if (!isset($input['usuario_id'])) {
  http_response_code(400);
  echo json_encode(["error" => "Falta el parámetro usuario_id"]);
  exit();
}

$usuario_id = intval($input['usuario_id']);

// Consulta
$sql = "
  SELECT DISTINCT p.nombre
  FROM usuarios AS u
  JOIN rol_permisos AS rp ON rp.rol_id = u.rol_id
  JOIN permisos AS p ON p.id = rp.permiso_id
  WHERE u.id = :usuario_id
";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
  $stmt->execute();
  $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);

  echo json_encode([
    "usuario_id" => $usuario_id,
    "permisos" => $permisos
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error en la base de datos", "detalles" => $e->getMessage()]);
}
?>
