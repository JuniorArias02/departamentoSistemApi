<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoNuevoPedido.php';
require_once __DIR__ . '/../notif_notificaciones/notificarNuevoPedidoCompras.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

// Validar campos obligatorios
$campos_obligatorios = [
    'fecha_solicitud',
    'proceso_solicitante',
    'tipo_solicitud',
    'observacion',
    'sede_id',
    'elaborado_por',
    'creador_por'
];
foreach ($campos_obligatorios as $campo) {
    if (empty($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo $campo es obligatorio"]);
        exit;
    }
}

try {
    // Consecutivo
    $stmtMax = $pdo->query("SELECT MAX(consecutivo) as max_consec FROM cp_pedidos");
    $ultimo = $stmtMax->fetch(PDO::FETCH_ASSOC);
    $consecutivo = ($ultimo['max_consec'] ?? 0) + 1;

    // Insertar pedido
    $sqlInsert = "INSERT INTO cp_pedidos (
        estado_compras, fecha_solicitud, proceso_solicitante, tipo_solicitud, consecutivo,
        observacion, sede_id, elaborado_por, elaborado_por_firma, proceso_compra, proceso_compra_firma,
        responsable_aprobacion, responsable_aprobacion_firma, creador_por, pedido_visto,
        observacion_diligenciado, estado_gerencia
    ) VALUES (
        'pendiente', :fecha_solicitud, :proceso_solicitante, :tipo_solicitud, :consecutivo,
        :observacion, :sede_id,  :elaborado_por, NULL, NULL, NULL,
        NULL, NULL, :creador_por, 0, NULL, 'pendiente'
    )";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':fecha_solicitud' => $data['fecha_solicitud'],
        ':proceso_solicitante' => $data['proceso_solicitante'],
        ':tipo_solicitud' => $data['tipo_solicitud'],
        ':consecutivo' => $consecutivo,
        ':observacion' => $data['observacion'],
        ':sede_id' => $data['sede_id'],
        ':elaborado_por' => $data['elaborado_por'],
        ':creador_por' => $data['creador_por']
    ]);

    $pedido_id = $pdo->lastInsertId();

    $sqlPedido = "
    SELECT p.fecha_solicitud, 
           ds.nombre AS proceso_solicitante,
           ts.nombre AS nombre_tipo,
           p.consecutivo,
           p.observacion,
           u.nombre_completo, 
           u.correo
    FROM cp_pedidos p
    JOIN usuarios u ON u.id = p.creador_por
    LEFT JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
    LEFT JOIN dependencias_sedes ds ON ds.id = p.proceso_solicitante
    WHERE p.id = :id
    LIMIT 1
";

    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute([':id' => $pedido_id]);
    $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);


    // Registrar actividad
    registrarActividad(
        $pdo,
        $data['creador_por'],
        "Creó un nuevo pedido con consecutivo {$consecutivo}",
        "cp_pedidos",
        $pedido_id
    );

    notificarNuevoPedidoCompras(
        $pdo,
        $pedido['fecha_solicitud'],
        $pedido['proceso_solicitante'],
        $pedido['nombre_tipo'],
        $pedido['observacion'],
        $pedido['consecutivo']
    );


    // Buscar correo y nombre del creador
    $stmtCreador = $pdo->prepare("SELECT nombre_completo, correo FROM usuarios WHERE id = :id LIMIT 1");
    $stmtCreador->execute([':id' => $data['creador_por']]);
    $creador = $stmtCreador->fetch(PDO::FETCH_ASSOC);

    if ($creador) {
        try {
            enviarCorreoNuevoPedido(
                $creador['correo'],
                $creador['nombre_completo'],
                $pedido['fecha_solicitud'],
                $pedido['proceso_solicitante'],
                $pedido['nombre_tipo'],
                $pedido['observacion'],
                $pedido['consecutivo']
            );
        } catch (Exception $e) {
            error_log("Error enviando correo al creador: " . $e->getMessage());
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Pedido creado y correo enviado al creador",
        "id" => $pedido_id,
        "consecutivo" => $consecutivo
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al crear el pedido: " . $e->getMessage()]);
}
