<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota'); // zona horaria

$data = json_decode(file_get_contents('php://input'), true);
$usuario_input = $data['usuario'] ?? null;
$codigo = $data['codigo'] ?? null;

if (!$usuario_input || !$codigo) {
    echo json_encode(['status' => false, 'message' => 'Falta usuario o código']);
    exit;
}

try {
    // Buscar usuario por nombre
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->bindParam(':usuario', $usuario_input, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['status' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    $id_usuario = $usuario['id'];

    // Buscar código válido
    $stmt = $pdo->prepare("
        SELECT id, creado, fecha_activacion
        FROM sec_codigo_verificacion
        WHERE id_usuario = :id_usuario AND codigo = :codigo
        LIMIT 1
    ");
    $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();
    $codigo_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$codigo_row) {
        echo json_encode(['status' => false, 'message' => 'Código inválido']);
        exit;
    }

    // Verificar si ya fue usado
    if ($codigo_row['fecha_activacion'] !== null) {
        echo json_encode(['status' => false, 'message' => 'Código ya se activo']);
        exit;
    }

    // Verificar que no haya pasado más de 1 hora usando strtotime()
    $creado_ts = strtotime($codigo_row['creado']);
    $ahora_ts = time();
    $diff = $ahora_ts - $creado_ts;

    if ($diff > 3600) { // 1 hora = 3600 segundos
        echo json_encode(['status' => false, 'message' => 'Código expirado']);
        exit;
    }

    // Marcar código como usado y agregar fecha de expiración (10 minutos después de ahora)
    $fecha_expiracion = date('Y-m-d H:i:s', time() + 600); // 10 minutos = 600 seg

    $stmt = $pdo->prepare("
        UPDATE sec_codigo_verificacion
        SET fecha_activacion = NOW(), fecha_expiracion = :fecha_expiracion
        WHERE id = :id
    ");
    $stmt->bindParam(':fecha_expiracion', $fecha_expiracion, PDO::PARAM_STR);
    $stmt->bindParam(':id', $codigo_row['id'], PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['status' => true, 'message' => 'Código válido']);

} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => 'Error en la base de datos', 'error' => $e->getMessage()]);
}
