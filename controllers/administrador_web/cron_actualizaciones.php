<?php
require_once __DIR__ . '/../../database/conexion.php';

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');
$hoy = date('Y-m-d');

// Cambiar a 'en progreso' si fecha_actualizacion es hoy
$sqlEnProgreso = "UPDATE actualizaciones_web 
                  SET estado = 'en progreso' 
                  WHERE fecha_actualizacion = :hoy 
                  AND estado != 'en progreso'";
$stmt = $pdo->prepare($sqlEnProgreso);
$stmt->execute([':hoy' => $hoy]);

// Cambiar a 'finalizado' si fecha_actualizacion < hoy
$sqlFinalizado = "UPDATE actualizaciones_web 
                  SET estado = 'finalizado' 
                  WHERE fecha_actualizacion < :hoy 
                  AND estado != 'finalizado'";
$stmt = $pdo->prepare($sqlFinalizado);
$stmt->execute([':hoy' => $hoy]);

// (Opcional) Asegurar que las que están en rango de visibilidad estén 'pendientes'
$sqlPendientes = "UPDATE actualizaciones_web 
                  SET estado = 'pendiente' 
                  WHERE :hoy BETWEEN mostrar_desde AND mostrar_hasta 
                  AND fecha_actualizacion > :hoy 
                  AND estado != 'pendiente'";
$stmt = $pdo->prepare($sqlPendientes);
$stmt->execute([':hoy' => $hoy]);

echo json_encode(['success' => true, 'message' => 'Actualizaciones revisadas']);
