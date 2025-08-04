<?php
require_once __DIR__ . '/../../database/conexion.php';

$config = $pdo->query("SELECT * FROM pc_config_cronograma LIMIT 1")->fetch();
$equipos = $pdo->query("SELECT * FROM pc_equipos WHERE estado = 'activo'")->fetchAll();

foreach ($equipos as $equipo) {
    $ultimo = $pdo->prepare("
        SELECT estado_cumplimiento, fecha_ultimo_mantenimiento 
        FROM pc_cronograma_mantenimientos 
        WHERE equipo_id = :id 
        ORDER BY fecha_ultimo_mantenimiento DESC 
        LIMIT 1
    ");
    $ultimo->execute(["id" => $equipo["id"]]);
    $dataUltimo = $ultimo->fetch();

    $fechaEvaluar = $dataUltimo["fecha_ultimo_mantenimiento"] ?? $equipo["fecha_ingreso"];
    $estadoUltimo = $dataUltimo["estado_cumplimiento"] ?? 'no_aplica';

    $fechaReferencia = new DateTime($fechaEvaluar);
    $hoy = new DateTime();

    // Validar si ya pasó 1 año desde ingreso (si estaba como no_aplica)
    if ($estadoUltimo === 'no_aplica') {
        $unAñoDespues = new DateTime($equipo["fecha_ingreso"]);
        $unAñoDespues->modify("+1 year");

        if ($hoy < $unAñoDespues) {
            continue; // aún no ha pasado el año, lo salta
        }

        $fechaReferencia = $unAñoDespues; // aquí empezamos validaciones
    }

    // Calcular la próxima fecha programada
    $proxima = clone $fechaReferencia;

    if ($config["meses_cumplimiento"]) {
        $proxima->modify("+{$config['meses_cumplimiento']} months");
    } elseif ($config["dias_cumplimiento"]) {
        $proxima->modify("+{$config['dias_cumplimiento']} days");
    }

    // Si ya pasó la fecha programada, registrar como mantenimiento pendiente
    if ($proxima <= $hoy) {
        $stmt = $pdo->prepare("
            INSERT INTO pc_cronograma_mantenimientos (
                equipo_id, fecha_programada, estado_cumplimiento, fecha_ultimo_mantenimiento
            ) VALUES (
                :id, :fecha, 'pendiente', :ultima
            )
        ");
        $stmt->execute([
            "id" => $equipo["id"],
            "fecha" => $proxima->format("Y-m-d"),
            "ultima" => $fechaReferencia->format("Y-m-d"),
        ]);
    }
}
