<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

if (!isset($_POST["mantenimiento_id"])) {
    echo json_encode([
        "status" => false,
        "message" => "Falta mantenimiento_id"
    ]);
    exit;
}

$mantenimiento_id = intval($_POST["mantenimiento_id"]);

$ext_permitidas = ['png'];
$directorio = __DIR__ . '/../../public/equipos/';

if (!file_exists($directorio)) {
    mkdir($directorio, 0755, true);
}

$resultados = [];

foreach (["firma_personal_cargo", "firma_sistemas"] as $campo) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $ext_permitidas)) {
            $resultados[$campo] = ["status" => false, "error" => "Formato no permitido"];
            continue;
        }

        $nombre_archivo = uniqid($campo . "_") . ".png";
        $ruta_absoluta = $directorio . $nombre_archivo;
        $ruta_relativa = "public/equipos/" . $nombre_archivo;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $ruta_absoluta)) {
            // Guardar en BD
            $stmt = $pdo->prepare("UPDATE pc_mantenimientos SET $campo = :ruta WHERE id = :id");
            $stmt->execute([
                "ruta" => $ruta_relativa,
                "id"   => $mantenimiento_id
            ]);

            $resultados[$campo] = ["status" => true, "path" => $ruta_relativa];
        } else {
            $resultados[$campo] = ["status" => false, "error" => "Error al mover el archivo"];
        }
    } else {
        $resultados[$campo] = ["status" => false, "error" => "No se recibió archivo"];
    }
}

echo json_encode([
    "status" => true,
    "message" => "Proceso completado",
    "resultados" => $resultados
]);
