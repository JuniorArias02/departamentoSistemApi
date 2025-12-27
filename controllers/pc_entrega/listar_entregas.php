<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}	

try {

    // ================================
    // 1️⃣ Traer todas las entregas
    // ================================
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.equipo_id,
            e.funcionario_id,
            e.fecha_entrega,
            e.firma_entrega,
            e.firma_recibe,
            e.estado,
            eq.nombre_equipo,
            eq.marca AS equipo_marca,
            eq.modelo AS equipo_modelo,
            eq.serial AS equipo_serial,
            eq.numero_inventario AS inventario_equipo,

            f.nombre AS funcionario_nombre,
            f.cedula AS funcionario_cedula
        FROM pc_entregas e
        LEFT JOIN pc_equipos eq ON eq.id = e.equipo_id
        LEFT JOIN personal f ON f.id = e.funcionario_id
        ORDER BY e.id DESC
    ");

    $stmt->execute();
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ================================
    // 2️⃣ Traer periféricos por entrega
    // ================================
    $stmtPeri = $pdo->prepare("
        SELECT 
            p.entrega_id,
            p.inventario_id,
            p.cantidad,
            p.observaciones,

            i.codigo,
            i.nombre,
            i.marca,
            i.modelo,
            i.serial
        FROM pc_perifericos_entregados p
        LEFT JOIN inventario i ON i.id = p.inventario_id
        ORDER BY p.entrega_id
    ");

    $stmtPeri->execute();
    $perifericos = $stmtPeri->fetchAll(PDO::FETCH_ASSOC);

    // Reorganizar periféricos por entrega_id
    $mapPeri = [];
    foreach ($perifericos as $p) {
        $mapPeri[$p['entrega_id']][] = $p;
    }

    // Adjuntar periféricos a cada entrega
    foreach ($entregas as &$entrega) {
        $id = $entrega['id'];
        $entrega['perifericos'] = $mapPeri[$id] ?? [];
    }

    echo json_encode([
        "success" => true,
        "data" => $entregas
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error interno: " . $e->getMessage()
    ]);
}
