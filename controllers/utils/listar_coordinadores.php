<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
  // Verificar conexión PDO
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new Exception('Conexión a la base de datos no disponible');
  }

  // Consulta SQL para obtener coordinadores
  $sql = "SELECT u.id, u.nombre_completo, u.usuario, r.nombre as rol 
          FROM usuarios u 
          JOIN rol r ON u.rol_id = r.id 
          WHERE r.nombre = 'coordinador'
          ORDER BY u.nombre_completo ASC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  
  $coordinadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  if ($coordinadores) {
    // Respuesta exitosa - solo los datos
    http_response_code(200);
    echo json_encode($coordinadores);
  } else {
    // No se encontraron coordinadores - array vacío
    http_response_code(200);
    echo json_encode([]);
  }
  
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([]);
}