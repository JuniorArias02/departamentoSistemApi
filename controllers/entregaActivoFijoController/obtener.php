<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $sql = "
        SELECT 
            e.id AS entrega_id,
            e.fecha_entrega,
            e.firma_quien_entrega,
            e.firma_quien_recibe,
            p.id AS personal_id,
            p.nombre AS personal_nombre,
            p.cedula AS personal_cedula,
            c.nombre AS cargo_nombre,
            e.sede_id,
			s.nombre AS sede_nombre
        FROM cp_entrega_activos_fijos e
        LEFT JOIN personal p ON e.personal_id = p.id
        LEFT JOIN p_cargo c ON p.cargo_id = c.id
		LEFT JOIN sedes s ON e.sede_id = s.id
        ORDER BY e.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ok" => true,
        "data" => $entregas
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}
?>
