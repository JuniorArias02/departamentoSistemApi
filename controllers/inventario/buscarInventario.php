 <?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    echo json_encode([
        "status" => false,
        "message" => "El código es obligatorio"
    ]);
    exit;
}

$codigo = trim($codigo);


try {
    $sql = "SELECT id, codigo, nombre, serial 
            FROM inventario 
            WHERE codigo = :codigo 
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();

    $inventario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inventario) {
        echo json_encode([
            "status" => true,
            "data" => $inventario
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Inventario no encontrado"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error en el servidor"
    ]);
}
