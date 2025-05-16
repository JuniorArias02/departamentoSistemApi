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
$camposRequeridos = ['nombre_equipo', 'marca', 'modelo', 'serie', 'registro_sanitario', 'clasificacion_riesgo', 'creado_por'];
foreach ($camposRequeridos as $campo) {
    if (empty($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' es obligatorio"]);
        exit;
    }
}

try {
    if (!empty($data['id'])) {
        // EDITAR
        $stmt = $pdo->prepare("UPDATE equipos_biomedicos SET 
            nombre_equipo = :nombre_equipo,
            marca = :marca,
            modelo = :modelo,
            serie = :serie,
            registro_sanitario = :registro_sanitario,
            clasificacion_riesgo = :clasificacion_riesgo
            WHERE id = :id");

        $stmt->execute([
            ':nombre_equipo' => $data['nombre_equipo'],
            ':marca' => $data['marca'],
            ':modelo' => $data['modelo'],
            ':serie' => $data['serie'],
            ':registro_sanitario' => $data['registro_sanitario'],
            ':clasificacion_riesgo' => $data['clasificacion_riesgo'],
            ':id' => $data['id']
        ]);

        echo json_encode(["msg" => "Equipo biomédico actualizado correctamente"]);
    } else {
        // CREAR
        $stmt = $pdo->prepare("INSERT INTO equipos_biomedicos 
            (nombre_equipo, marca, modelo, serie, registro_sanitario, clasificacion_riesgo, fecha_creacion, creado_por)
            VALUES (:nombre_equipo, :marca, :modelo, :serie, :registro_sanitario, :clasificacion_riesgo, NOW(), :creado_por)");

        $stmt->execute([
            ':nombre_equipo' => $data['nombre_equipo'],
            ':marca' => $data['marca'],
            ':modelo' => $data['modelo'],
            ':serie' => $data['serie'],
            ':registro_sanitario' => $data['registro_sanitario'],
            ':clasificacion_riesgo' => $data['clasificacion_riesgo'],
            ':creado_por' => $data['creado_por'],
        ]);

        echo json_encode(["msg" => "Equipo biomédico registrado con éxito"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el equipo: " . $e->getMessage()]);
}
