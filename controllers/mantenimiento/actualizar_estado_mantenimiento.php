<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_crud.php';


try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validar datos de entrada
    if (!isset($data['id']) || !isset($data['usuario_id']) || !isset($data['esta_revisado'])) {
        http_response_code(400);
        echo json_encode(["error" => "Se requieren: ID del mantenimiento, ID del usuario y estado de revisión"]);
        exit;
    }

    $mantenimientoId = filter_var($data['id'], FILTER_VALIDATE_INT);
    $usuarioId = filter_var($data['usuario_id'], FILTER_VALIDATE_INT);
    $estaRevisado = filter_var($data['esta_revisado'], FILTER_VALIDATE_INT);
    
    if ($mantenimientoId === false || $usuarioId === false || ($estaRevisado !== 0 && $estaRevisado !== 1)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos inválidos. Estado debe ser 0 o 1"]);
        exit;
    }

    // Obtener fecha actual
    $fechaActual = date('Y-m-d H:i:s');

    // Actualizar el mantenimiento (sin fecha_ultima_actualizacion)
    $stmt = $pdo->prepare("
        UPDATE mantenimientos_freezer 
        SET 
            esta_revisado = ?,
            revisado_por = ?,
            fecha_revisado = ?
        WHERE 
            id = ?
    ");

    $success = $stmt->execute([
        $estaRevisado, // Usamos el valor recibido (0 o 1)
        $estaRevisado ? $usuarioId : null, // Solo guarda usuario si está marcando como revisado
        $estaRevisado ? $fechaActual : null, // Solo guarda fecha si está marcando como revisado
        $mantenimientoId
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Mantenimiento no encontrado o no se realizaron cambios'
        ]);
        exit;
    }

    // Obtener datos actualizados
    $stmtMantenimiento = $pdo->prepare("
        SELECT 
            mf.id,
            mf.esta_revisado,
            mf.fecha_revisado,
            u.nombre_completo AS nombre_revisor
        FROM 
            mantenimientos_freezer mf
        LEFT JOIN 
            usuarios u ON mf.revisado_por = u.id
        WHERE 
            mf.id = ?
    ");
    $stmtMantenimiento->execute([$mantenimientoId]);
    $mantenimientoActualizado = $stmtMantenimiento->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Estado de mantenimiento actualizado correctamente',
        'data' => [
            'id' => $mantenimientoActualizado['id'],
            'esta_revisado' => (bool)$mantenimientoActualizado['esta_revisado'], // Convertimos a booleano para el front
            'fecha_revisado' => $mantenimientoActualizado['fecha_revisado'],
            'revisado_por' => $estaRevisado ? $usuarioId : null,
            'nombre_revisor' => $estaRevisado ? $mantenimientoActualizado['nombre_revisor'] : null
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al actualizar el mantenimiento: ' . $e->getMessage()
    ]);
}