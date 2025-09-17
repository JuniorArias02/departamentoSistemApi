<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.nombre_equipo,
        e.marca,
        e.modelo,
        e.serial,
        e.tipo,
        e.propiedad,
        e.ip_fija,
        e.numero_inventario,
        e.estado,
        e.imagen_url,
        e.fecha_entrega,
        e.descripcion_general,
        e.garantia_meses,   
        e.forma_adquisicion,
        e.observaciones,
        e.repuestos_principales,
        e.recomendaciones,
        e.equipos_adicionales,
        e.fecha_ingreso,

        -- 🔹 Días restantes (último mantenimiento o fecha de ingreso)
        DATEDIFF(
            DATE_ADD(
                COALESCE(
                    (
                        SELECT MAX(m.fecha)
                        FROM pc_mantenimientos m
                        WHERE m.equipo_id = e.id
                    ),
                    e.fecha_ingreso -- si no hay mantenimientos, usa la fecha de ingreso
                ),
                INTERVAL IFNULL(cc.meses_cumplimiento,0) MONTH
            ) + INTERVAL IFNULL(cc.dias_cumplimiento,0) DAY,
            CURDATE()
        ) AS dias_restantes,

        -- Características técnicas
        c.procesador,
        c.memoria_ram,
        c.disco_duro,
        c.tarjeta_video,
        c.tarjeta_red,
        c.tarjeta_sonido,
        c.usb,
        c.unidad_cd,
        c.parlantes,
        c.drive,
        c.monitor,
        c.teclado,
        c.mouse,
        c.internet,
        c.velocidad_red,
        c.capacidad_disco,

        -- Personal
        p.nombre AS responsable_nombre,
        p.cedula AS responsable_cedula,
        p.telefono AS responsable_telefono,
        ps.nombre AS responsable_cargo,
        p.proceso AS responsable_proceso,

        -- Área
        a.nombre AS area_nombre,

        -- Sede
        s.nombre AS sede_nombre,

        -- Licencias de software
        l.windows,
        l.office,
        l.nitro,

        -- Mantenimientos
        (
            SELECT COUNT(*) 
            FROM pc_mantenimientos m 
            WHERE m.equipo_id = e.id
        ) AS numero_mantenimientos,

        (
            SELECT MAX(m.fecha) 
            FROM pc_mantenimientos m 
            WHERE m.equipo_id = e.id
        ) AS fecha_ultimo_mantenimiento

    FROM pc_equipos e
    LEFT JOIN pc_caracteristicas_tecnicas c ON e.id = c.equipo_id
    LEFT JOIN personal p ON e.responsable_id = p.id
    LEFT JOIN areas a ON e.area_id = a.id
    LEFT JOIN sedes s ON e.sede_id = s.id
    LEFT JOIN pc_licencias_software l ON e.id = l.equipo_id
    -- 🔹 Join a la tabla de configuración
    LEFT JOIN pc_config_cronograma cc ON 1=1
    LEFT JOIN p_cargo ps ON p.cargo_id = ps.id
    ORDER BY e.id DESC
");


	$stmt->execute();
	$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (empty($equipos)) {
		echo json_encode([
			"status" => true,
			"data" => [],
			"message" => "No se encontraron equipos registrados."
		]);
		exit;
	}

	echo json_encode([
		"status" => true,
		"data" => $equipos
	]);
} catch (PDOException $e) {
	echo json_encode([
		"status" => false,
		"message" => "Error al obtener los equipos: " . $e->getMessage()
	]);
}
