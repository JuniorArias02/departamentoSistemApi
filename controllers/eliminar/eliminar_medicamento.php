<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        echo json_encode(['error' => 'ID del medicamento no proporcionado']);
        http_response_code(400);
        exit();
    }

    $id = $data['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM medicamentos WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['mensaje' => 'Medicamento eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error al eliminar el medicamento: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
