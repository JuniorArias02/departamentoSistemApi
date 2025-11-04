<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../sec_intentos_login/intentos_login.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoNotificacionLogin.php';
require_once __DIR__ . '/../utils/session_info.php';
require_once __DIR__ . '/../sec_ip_bloqueadas/verificar_bloqueo.php';
require_once __DIR__ . '/../sec_ip_bloqueadas/calcularTiempoRestante.php';

$data = json_decode(file_get_contents("php://input"), true);
$usuario = $data['usuario'] ?? '';
$contrasena = $data['contrasena'] ?? '';

$stmt = $pdo->prepare("
    SELECT u.*, r.nombre AS nombre_rol
    FROM usuarios u
    LEFT JOIN rol r ON u.rol_id = r.id
    WHERE u.usuario = :usuario
");
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$infoSesion = obtenerInfoSesion();
$ip = $infoSesion['ip'];

$maxIntentos = 5;          // máximo de intentos antes de bloquear
$ventanaMinutos = 10;      // ventana de tiempo para contar intentos fallidos

$expiracion = verificar_bloqueo($pdo, $ip, $maxIntentos, $ventanaMinutos);
$tiempoRestante = calcularTiempoRestante($expiracion);

// 🔒 Verificar si ya está bloqueada
if ($expiracion) {
    http_response_code(403);
    echo json_encode([
        "status" => "bloqueado",
        "msg" => "Demasiados intentos fallidos. Intenta más tarde.",
        "tiempo_restante" => max(0, $tiempoRestante)
    ]);
    exit;
}

// ✅ Login correcto
if ($user && password_verify($contrasena, $user['contrasena'])) {
    registrar_intento_login($pdo, $usuario, $user['id'], 1, $infoSesion['ip']);

    // Notificación por correo con IP, fecha y equipo
    enviarCorreoNotificacionLogin(
        $user['correo'],
        $user['nombre_completo'],
        $infoSesion['ip'],
        $infoSesion['fecha'],
        $infoSesion['user_agent']
    );

    echo json_encode([
        "status" => "ok",
        "msg" => "Login exitoso",
        "usuario" => [
            "id" => $user['id'],
            "nombre_completo" => $user['nombre_completo'],
            "rol" => $user['nombre_rol']
        ]
    ]);
    exit;
}

// ❌ Login incorrecto
registrar_intento_login($pdo, $usuario, $user['id'] ?? null, 0, $infoSesion['ip']);

// Buscar última expiración de bloqueo
$stmt = $pdo->prepare("
    SELECT MAX(fecha_expiracion) 
    FROM sec_ip_bloqueadas 
    WHERE ip = :ip
");
$stmt->execute(['ip' => $ip]);
$ultimaExpiracion = $stmt->fetchColumn();

// Contar intentos fallidos recientes
$sql = "
    SELECT COUNT(*) 
    FROM sec_intentos_login 
    WHERE ip = :ip 
      AND exito = 0 
      AND fecha > DATE_SUB(NOW(), INTERVAL :min MINUTE)
";
$params = [
    'ip' => $ip,
    'min' => $ventanaMinutos
];

if ($ultimaExpiracion) {
    $sql .= " AND fecha > :ultimaExpiracion";
    $params['ultimaExpiracion'] = $ultimaExpiracion;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fallos = $stmt->fetchColumn();

// Calcular intentos restantes
$intentosRestantes = max(0, $maxIntentos - $fallos);

echo json_encode([
    "status" => "error",
    "msg" => "Credenciales incorrectas",
    "intentos_restantes" => $intentosRestantes
]);


