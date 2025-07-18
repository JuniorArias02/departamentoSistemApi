<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

date_default_timezone_set('America/Bogota');

$camposObligatorios = [
    "titulo",
    "codigo",
    "modelo",
    "dependencia",
    "sede_id",
    "nombre_receptor",
    "creado_por"
];

foreach ($camposObligatorios as $campo) {
    if (!isset($_POST[$campo]) || trim($_POST[$campo]) === "") {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
        exit;
    }
}

$esEdicion = !empty($_POST['id']);
$data = $_POST;
$rutasGuardadas = [];

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!empty($_FILES['imagenes']['name'][0])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $i => $tmpName) {
        $tipo = $_FILES['imagenes']['type'][$i];

        if (!in_array($tipo, $tiposPermitidos)) continue;

        $ext = pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION);
        $nombreImg = uniqid("img_") . "." . $ext;
        $rutaRelativa = "public/mantenimientos/" . $nombreImg;
        $rutaGuardado = __DIR__ . "/../../" . $rutaRelativa;

        if (move_uploaded_file($tmpName, $rutaGuardado)) {
            $rutasGuardadas[] = $rutaRelativa;
        }
    }
} else if (!$esEdicion) {
    http_response_code(400);
    echo json_encode(["error" => "Debes subir al menos una imagen."]);
    exit;
}

try {
    if ($esEdicion) {
        // Validar edición
        $stmtCheck = $pdo->prepare("SELECT revisado_por, imagen FROM mantenimientos WHERE id = :id");
        $stmtCheck->execute(["id" => $data["id"]]);
        $mantenimiento = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($mantenimiento && $mantenimiento['revisado_por'] !== null) {
            http_response_code(403);
            echo json_encode(["error" => "No se puede modificar un mantenimiento ya revisado"]);
            exit;
        }

        if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['MANTENIMIENTOS']['EDITAR'])) {
            http_response_code(403);
            echo json_encode(["error" => "No tienes permiso para editar este mantenimiento."]);
            exit;
        }

        // Mantener imágenes si no se suben nuevas
        $imagenesAnteriores = json_decode($mantenimiento['imagen'], true) ?? [];

        $imagenesFinal = json_encode(
            !empty($rutasGuardadas)
                ? array_merge($imagenesAnteriores, $rutasGuardadas)
                : $imagenesAnteriores
        );

        $stmt = $pdo->prepare("UPDATE mantenimientos SET 
            titulo = :titulo,
            codigo = :codigo,
            modelo = :modelo,
            dependencia = :dependencia,
            sede_id = :sede_id,
            nombre_receptor = :nombre_receptor,
            imagen = :imagen,
            descripcion = :descripcion,
            fecha_ultima_actualizacion = NOW()
            WHERE id = :id");

        $stmt->execute([
            "titulo" => $data["titulo"],
            "codigo" => $data["codigo"],
            "modelo" => $data["modelo"],
            "dependencia" => $data["dependencia"],
            "sede_id" => $data["sede_id"],
            "nombre_receptor" => $data["nombre_receptor"],
            "imagen" => $imagenesFinal,
            "descripcion" => $data["descripcion"] ?? null,
            "id" => $data["id"]
        ]);

        registrarActividad($pdo, $data["creado_por"], "Actualizó mantenimiento con título '{$data["titulo"]}'", "mantenimientos", $data["id"]);

        echo json_encode(["msg" => "Mantenimiento actualizado con éxito"]);
    } else {
        // CREAR NUEVO MANTENIMIENTO
        $stmt = $pdo->prepare("INSERT INTO mantenimientos
            (titulo, codigo, modelo, dependencia, sede_id, nombre_receptor, imagen, descripcion, creado_por, fecha_creacion)
            VALUES
            (:titulo, :codigo, :modelo, :dependencia, :sede_id, :nombre_receptor, :imagen, :descripcion, :creado_por, NOW())");

        $stmt->execute([
            "titulo" => $data["titulo"],
            "codigo" => $data["codigo"],
            "modelo" => $data["modelo"],
            "dependencia" => $data["dependencia"],
            "sede_id" => $data["sede_id"],
            "nombre_receptor" => $data["nombre_receptor"],
            "imagen" => json_encode($rutasGuardadas),
            "descripcion" => $data["descripcion"] ?? null,
            "creado_por" => $data["creado_por"]
        ]);

        $idInsertado = $pdo->lastInsertId();

        registrarActividad($pdo, $data["creado_por"], "Creó mantenimiento con título '{$data["titulo"]}'", "mantenimientos", $idInsertado);

        echo json_encode([
            "msg" => "Mantenimiento registrado con éxito",
            "id" => $idInsertado
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el mantenimiento: " . $e->getMessage()]);
}
