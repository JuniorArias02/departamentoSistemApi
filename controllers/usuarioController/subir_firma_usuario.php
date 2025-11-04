<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

try {
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

    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }

    if (isset($_FILES["firma_digital"]) && $_FILES["firma_digital"]["error"] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES["firma_digital"]["name"], PATHINFO_EXTENSION));
        if (!in_array($extension, $ext_permitidas)) {
            throw new Exception("Formato no permitido");
        }

        $nombre_archivo = uniqid("firma_usuario_") . ".png";
        $ruta_absoluta = $directorio . $nombre_archivo;
        $ruta_relativa = "public/firmas/" . $nombre_archivo;

        if (!move_uploaded_file($_FILES["firma_digital"]["tmp_name"], $ruta_absoluta)) {
            throw new Exception("Error al mover el archivo");
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = :ruta WHERE id = :id");
        $stmt->execute([
            "ruta" => $ruta_relativa,
            "id"   => $usuario_id
        ]);

        echo json_encode([
            "status" => true,
            "message" => "Firma subida correctamente",
            "path" => $ruta_relativa
        ]);
    } else {
        throw new Exception("No se recibió archivo");
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
