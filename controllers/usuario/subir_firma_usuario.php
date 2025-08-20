  <?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

// Validar que llegue el id del usuario
if (!isset($_POST["usuario_id"])) {
    echo json_encode([
        "status" => false,
        "message" => "Falta usuario_id"
    ]);
    exit;
}

$usuario_id = intval($_POST["usuario_id"]);

$ext_permitidas = ['png'];
$directorio = __DIR__ . '/../../public/firmas/';

// Crear carpeta si no existe
if (!file_exists($directorio)) {
    mkdir($directorio, 0755, true);
}

$resultado = [];

if (isset($_FILES["firma_digital"]) && $_FILES["firma_digital"]["error"] === UPLOAD_ERR_OK) {
    $extension = strtolower(pathinfo($_FILES["firma_digital"]["name"], PATHINFO_EXTENSION));

    if (!in_array($extension, $ext_permitidas)) {
        $resultado = ["status" => false, "error" => "Formato no permitido"];
    } else {
        // Generar nombre único
        $nombre_archivo = uniqid("firma_usuario_") . ".png";
        $ruta_absoluta = $directorio . $nombre_archivo;
        $ruta_relativa = "public/firmas/" . $nombre_archivo;

        if (move_uploaded_file($_FILES["firma_digital"]["tmp_name"], $ruta_absoluta)) {
            // Guardar en BD
            $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = :ruta WHERE id = :id");
            $stmt->execute([
                "ruta" => $ruta_relativa,
                "id"   => $usuario_id
            ]);

            $resultado = ["status" => true, "path" => $ruta_relativa];
        } else {
            $resultado = ["status" => false, "error" => "Error al mover el archivo"];
        }
    }
} else {
    $resultado = ["status" => false, "error" => "No se recibió archivo"];
}

echo json_encode([
    "status" => true,
    "message" => "Proceso completado",
    "resultado" => $resultado
]);
