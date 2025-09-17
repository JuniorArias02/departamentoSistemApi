<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';


try {
    $sql = "SELECT 
                m.id,
                m.equipo_id,
                e.nombre_equipo,
                m.tipo_mantenimiento,
                m.descripcion,
				m.estado,
                m.fecha,
                m.empresa_responsable_id,
                m.repuesto,
                m.cantidad_repuesto,
                m.costo_repuesto,
                m.nombre_repuesto,
                m.responsable_mantenimiento,
				u.nombre_completo AS responsable_nombre,
                m.firma_personal_cargo,
                m.firma_sistemas,
                m.creado_por,
                m.fecha_creacion
            FROM pc_mantenimientos m
            INNER JOIN pc_equipos e ON m.equipo_id = e.id
			INNER JOIN usuarios u ON m.responsable_mantenimiento = u.id
            ORDER BY m.fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $mantenimientos
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener mantenimientos: " . $e->getMessage()
    ]);
}
