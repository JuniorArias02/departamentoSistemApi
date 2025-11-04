<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';


$data = json_decode(file_get_contents("php://input"), true);

$condicionesTexto = [];
$condicionesOtros = [];
$params = [];

if (!empty($data['filtroTexto'])) {
    $condicionesTexto[] = "i.codigo LIKE :filtroTexto";
    $condicionesTexto[] = "i.serial LIKE :filtroTexto";
    $condicionesTexto[] = "i.dependencia LIKE :filtroTexto";
    $condicionesTexto[] = "i.nombre LIKE :filtroTexto";
    $params[':filtroTexto'] = "%" . $data['filtroTexto'] . "%";
}

if (!empty($data['sede_nombre'])) {
    $condicionesOtros[] = "s.nombre LIKE :sede_nombre";
    $params[':sede_nombre'] = "%" . $data['sede_nombre'] . "%";
}


$whereTexto = "";
if (!empty($condicionesTexto)) {
    $whereTexto = "(" . implode(" OR ", $condicionesTexto) . ")";
}

$whereOtros = "";
if (!empty($condicionesOtros)) {
    $whereOtros = implode(" AND ", $condicionesOtros);
}

$condicionFinal = [];
if ($whereTexto) $condicionFinal[] = $whereTexto;
if ($whereOtros) $condicionFinal[] = $whereOtros;

$sql = "SELECT i.*, s.nombre AS sede_nombre
        FROM inventario i
        LEFT JOIN sedes s ON i.sede_id = s.id";

if (!empty($condicionFinal)) {
    $sql .= " WHERE " . implode(" AND ", $condicionFinal);
}

try {
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($resultado);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al buscar inventario: " . $e->getMessage()]);
}

