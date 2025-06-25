<?php
ini_set('memory_limit', '512M');  // 512MB de memoria
ini_set('post_max_size', '100M');  // 100MB máximo POST
ini_set('upload_max_filesize', '96M'); // 96MB máximo upload
ini_set('max_execution_time', '600'); // 10 minutos timeout
ini_set('max_input_time', '300'); // 5 minutos para recibir datos


require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../services/ImageService.php';

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

    if (trim($data[$campo]) === "") {
        http_response_code(400);
        echo json_encode(["error" => $campo === "imagen"
            ? "Debe subir una imagen del mantenimiento."
            : "El campo '$campo' no puede estar vacío."]);
        exit;
    }
}

// Validar que la imagen sea base64 y de tipo imagen
if (!preg_match('/^data:image\/(png|jpg|jpeg|webp|gif);base64,/', $data['imagen'])) {
    http_response_code(400);
    echo json_encode(["error" => "Solo se permiten archivos de imagen (jpg, png, jpeg, webp, gif)."]);
    exit;
}

// Procesar imagen con compresión
set_time_limit(600); // 10 minutos máximo

try {
    $imageResult = ImageService::uploadAndCompressImage(
        $data['imagen'],
        __DIR__ . '/../../public/mantenimientos/',
        1600,   // Ancho máximo aumentado para imágenes grandes
        null    // La calidad ahora se calcula automáticamente
    );

    $data['imagen'] = $imageResult['public_url'];

    // Registrar información de compresión si es necesario
    error_log("Imagen procesada - Original: {$imageResult['original_size']} bytes, Final: {$imageResult['size']} bytes, Calidad: {$imageResult['quality_applied']}");
} catch (RuntimeException $e) {
    http_response_code(400);
    die(json_encode([
        'error' => $e->getMessage(),
        'max_size' => '90MB',
        'allowed_types' => 'JPEG, PNG, WEBP, GIF'
    ]));
}
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
        registrarActividad(
            $pdo,
            $data['creado_por'],
            "Creo Mantenimiento con Titulo {$data['titulo']}",
            "mantenimientos",
            $data['id']
        );

        echo json_encode([
            "msg" => "Mantenimiento de freezer registrado con éxito",
            "id" => $pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el mantenimiento: " . $e->getMessage()]);
}
