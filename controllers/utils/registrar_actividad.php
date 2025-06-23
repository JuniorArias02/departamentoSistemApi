<?php
function registrarActividad($pdo, $usuario_id, $accion, $tabla, $registro_id, $fecha = null) {
    // Si no se pasa una fecha, usamos la actual en formato Colombia
    date_default_timezone_set('America/Bogota');
    $fecha = $fecha ?? date("Y-m-d H:i:s");

    try {
        $stmt = $pdo->prepare("
            INSERT INTO actividades (usuario_id, accion, tabla_afectada, registro_id, fecha)
            VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :fecha)
        ");

        $stmt->execute([
            "usuario_id"     => $usuario_id,
            "accion"         => $accion,
            "tabla_afectada" => $tabla,
            "registro_id"    => $registro_id,
            "fecha"          => $fecha
        ]);
    } catch (PDOException $e) {
        // No rompemos el flujo si falla la actividad, pero lo dejamos logueado por si acaso
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
