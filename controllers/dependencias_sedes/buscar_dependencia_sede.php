<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['sede_id'])) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "msg" => "Falta el parámetro sede_id"
        ]);
        exit;
    }

    $sedeId = intval($_GET['sede_id']);

    try {
        $query = "SELECT d.id, d.nombre 
                  FROM dependencias_sedes d
                  INNER JOIN sedes s ON s.id = d.sede_id
                  WHERE d.sede_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$sedeId]);

        $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "ok" => true,
            "sede_id" => $sedeId,
            "dependencias" => $dependencias
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "ok" => false,
            "msg" => "Error en la base de datos",
            "error" => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "msg" => "Método no permitido"
    ]);
}
