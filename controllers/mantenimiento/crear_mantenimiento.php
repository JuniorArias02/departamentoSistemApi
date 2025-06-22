<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

date_default_timezone_set('America/Bogota');

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$camposObligatorios = [
    "titulo",
    "codigo",
    "modelo",
    "dependencia",
    "sede_id",
    "nombre_receptor",
    "creado_por",
    "imagen"
];

foreach ($camposObligatorios as $campo) {
    if (!isset($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
        exit;
    }

    if ($campo === "imagen" && trim($data[$campo]) === "") {
        http_response_code(400);
        echo json_encode(["error" => "Debe subir una imagen del mantenimiento."]);
        exit;
    }

    if ($campo !== "imagen" && trim($data[$campo]) === "") {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' no puede estar vacío."]);
        exit;
    }
}


// Validar que la imagen sea base64 y de tipo imagen
if (!preg_match('/^data:image\/(png|jpg|jpeg|webp|gif);base64,/', $data['imagen'])) {
    http_response_code(400);
    echo json_encode(["error" => "Solo se permiten archivos de imagen (jpg, png, jpeg, webp, gif)."]);
    exit;
}

// Procesar imagen
$extension = explode('/', explode(';', $data['imagen'])[0])[1];
$nombreImagen = uniqid('img_') . '.' . $extension;
$rutaRelativa = 'public/mantenimientos/' . $nombreImagen;
$rutaGuardado = __DIR__ . '/../../' . $rutaRelativa;

$imagenBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $data['imagen']);
$imagenBase64 = str_replace(' ', '+', $imagenBase64);

if (!file_put_contents($rutaGuardado, base64_decode($imagenBase64))) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la imagen en el servidor"]);
    exit;
}

// Guardar la ruta en el array
$data['imagen'] = $rutaRelativa;

try {
    if (!empty($data['id'])) {
        $stmtCheck = $pdo->prepare("SELECT revisado_por FROM mantenimientos WHERE id = :id");
        $stmtCheck->execute(["id" => $data["id"]]);
        $mantenimiento = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($mantenimiento && $mantenimiento['revisado_por'] !== null) {
            http_response_code(403);
            echo json_encode(["error" => "No se puede modificar un mantenimiento ya revisado"]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE mantenimientos SET 
            titulo = :titulo,
            codigo = :codigo,
            modelo = :modelo,
            dependencia = :dependencia,
            sede_id = :sede_id,
            nombre_receptor = :nombre_receptor,
            imagen = :imagen,
            descripcion = :descripcion
            WHERE id = :id");


        if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['MANTENIMIENTOS']['EDITAR'])) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Acceso denegado. No tienes permiso para ver datos de inventario."
            ]);
            exit();
        }


        $stmt->execute([
            "titulo" => $data["titulo"],
            "codigo" => $data["codigo"],
            "modelo" => $data["modelo"],
            "dependencia" => $data["dependencia"],
            "sede_id" => $data["sede_id"],
            "nombre_receptor" => $data["nombre_receptor"],
            "imagen" => $data["imagen"],
            "descripcion" => $data["descripcion"] ?? null,
            "id" => $data["id"]
        ]);
        $fechaColombia = date('Y-m-d H:i:s');

        $stmtAct = $pdo->prepare("INSERT INTO actividades 
            (usuario_id, accion, tabla_afectada, registro_id, fecha)
            VALUES 
            (:usuario_id, :accion, :tabla_afectada, :registro_id, :fecha)");

        $stmtAct->execute([
            "usuario_id" => $data["creado_por"],
            "accion" => "Actualizó el mantenimiento IPS '" . $data["titulo"] . "'",
            "tabla_afectada" => "mantenimientos",
            "registro_id" => $data["id"],
            "fecha" => $fechaColombia
        ]);

        echo json_encode(["msg" => "Mantenimiento  actualizado con éxito"]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO mantenimientos
            (titulo, codigo, modelo, dependencia, sede_id, nombre_receptor, 
             imagen, descripcion, creado_por, fecha_creacion) 
            VALUES 
            (:titulo, :codigo, :modelo, :dependencia, :sede_id, :nombre_receptor, 
             :imagen, :descripcion, :creado_por, NOW())");

        $stmt->execute([
            "titulo" => $data["titulo"],
            "codigo" => $data["codigo"],
            "modelo" => $data["modelo"],
            "dependencia" => $data["dependencia"],
            "sede_id" => $data["sede_id"],
            "nombre_receptor" => $data["nombre_receptor"],
            "imagen" => $data["imagen"],
            "descripcion" => $data["descripcion"] ?? null,
            "creado_por" => $data["creado_por"]
        ]);

        $idInsertado = $pdo->lastInsertId();
        $fechaColombia = date('Y-m-d H:i:s');



        // REGISTRAR ACTIVIDAD DE CREACIÓN
        $stmtAct = $pdo->prepare("INSERT INTO actividades 
            (usuario_id, accion, tabla_afectada, registro_id, fecha)
            VALUES 
            (:usuario_id, :accion, :tabla_afectada, :registro_id, :fecha)");

        if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['MANTENIMIENTOS']['CREAR'])) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Acceso denegado. No tienes permiso para ver datos de inventario."
            ]);
            exit();
        }

        $stmtAct->execute([
            "usuario_id" => $data["creado_por"],
            "accion" => "Creó el mantenimiento de IPS'" . $data["titulo"] . "'",
            "tabla_afectada" => "mantenimientos",
            "registro_id" => $idInsertado,
            "fecha" => $fechaColombia
        ]);


        echo json_encode([
            "msg" => "Mantenimiento de freezer registrado con éxito",
            "id" => $pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el mantenimiento: " . $e->getMessage()]);
}
