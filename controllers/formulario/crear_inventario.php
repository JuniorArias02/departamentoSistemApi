<?php
require_once '../../database/conexion.php';
date_default_timezone_set('America/Bogota');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: https://formulario-medico.vercel.app");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$campos = ['codigo', 'nombre', 'creado_por'];
foreach ($campos as $campo) {
    if (!isset($data[$campo]) || trim($data[$campo]) === '') {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
        exit;
    }
}

try {
    if (!empty($data['id'])) {
        // EDITAR INVENTARIO - Parte existente
        $stmt = $pdo->prepare("UPDATE inventario SET 
            codigo = :codigo,
            nombre = :nombre,
            dependencia = :dependencia,
            responsable = :responsable,
            marca = :marca,
            modelo = :modelo,
            serial = :serial,
            sede_id = :sede_id,
            fecha_creacion = NOW()
            WHERE id = :id");

        $stmt->execute([
            "codigo" => $data["codigo"],
            "nombre" => $data["nombre"],
            "dependencia" => $data["dependencia"] ?? null,
            "responsable" => $data["responsable"] ?? null,
            "marca" => $data["marca"] ?? null,
            "modelo" => $data["modelo"] ?? null,
            "serial" => $data["serial"] ?? null,
            "sede_id" => $data["sede_id"] ?? null,
            "id" => $data["id"]
        ]);
$fechaColombia = date('Y-m-d H:i:s');

        // REGISTRAR ACTIVIDAD DE ACTUALIZACIÓN
        $stmtAct = $pdo->prepare("INSERT INTO actividades 
            (usuario_id, accion, tabla_afectada, registro_id, fecha)
            VALUES 
            (:usuario_id, :accion, :tabla_afectada, :registro_id, :fecha)");
        
        $stmtAct->execute([
            "usuario_id" => $data["creado_por"],
            "accion" => "Actualizó el item '".$data["nombre"]."' en el inventario",
            "tabla_afectada" => "inventario",
            "registro_id" => $data["id"],
            "fecha" => $fechaColombia
        ]);

        // Traer datos actualizados (parte existente)
        $stmt2 = $pdo->prepare("SELECT * FROM inventario WHERE id = :id");
        $stmt2->execute(["id" => $data["id"]]);
        $registro = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "msg" => "Inventario actualizado con éxito",
            "data" => $registro
        ]);

    } else {
        // CREAR INVENTARIO - Parte existente
        $stmt = $pdo->prepare("INSERT INTO inventario 
            (codigo, nombre, dependencia, responsable, marca, modelo, serial, sede_id, creado_por)
            VALUES 
            (:codigo, :nombre, :dependencia, :responsable, :marca, :modelo, :serial, :sede_id, :creado_por)");

        $stmt->execute([
            "codigo" => $data["codigo"],
            "nombre" => $data["nombre"],
            "dependencia" => $data["dependencia"] ?? null,
            "responsable" => $data["responsable"] ?? null,
            "marca" => $data["marca"] ?? null,
            "modelo" => $data["modelo"] ?? null,
            "serial" => $data["serial"] ?? null,
            "sede_id" => $data["sede_id"] ?? null,
            "creado_por" => $data["creado_por"]
        ]);

        $idInsertado = $pdo->lastInsertId();
$fechaColombia = date('Y-m-d H:i:s');

        // REGISTRAR ACTIVIDAD DE CREACIÓN
        $stmtAct = $pdo->prepare("INSERT INTO actividades 
            (usuario_id, accion, tabla_afectada, registro_id, fecha)
            VALUES 
            (:usuario_id, :accion, :tabla_afectada, :registro_id,:fecha)");
        
        $stmtAct->execute([
            "usuario_id" => $data["creado_por"],
            "accion" => "Creó el item '".$data["nombre"]."' en el inventario",
            "tabla_afectada" => "inventario",
            "registro_id" => $idInsertado,
            "fecha" => $fechaColombia
        ]);

        // Traer datos insertados (parte existente)
        $stmt2 = $pdo->prepare("SELECT * FROM inventario WHERE id = :id");
        $stmt2->execute(["id" => $idInsertado]);
        $registro = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "msg" => "Inventario registrado con éxito",
            "data" => $registro
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el inventario: " . $e->getMessage()]);
}