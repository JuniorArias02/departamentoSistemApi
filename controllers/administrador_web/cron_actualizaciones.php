<?php
require_once '/home/u528159717/public_html/deparSistemApi/database/conexion.php';
date_default_timezone_set('America/Bogota');
file_put_contents("log.txt", "Se ejecutó\n", FILE_APPEND);
$hoy = date('Y-m-d');

// 1. Finalizar actualizaciones pasadas
$sqlFinalizado = "UPDATE actualizaciones_web 
                  SET estado = 'finalizado' 
                  WHERE DATE(fecha_actualizacion) < :hoy 
                  AND estado != 'finalizado'";
$pdo->prepare($sqlFinalizado)->execute([':hoy' => $hoy]);

// 2. Marcar como en progreso si es hoy
$sqlEnProgreso = "UPDATE actualizaciones_web 
                  SET estado = 'en progreso' 
                  WHERE DATE(fecha_actualizacion) = :hoy 
                  AND estado != 'en progreso'";
$pdo->prepare($sqlEnProgreso)->execute([':hoy' => $hoy]);

// 3. Marcar como pendiente si está en rango pero aún no es la fecha de actualización
$sqlPendientes = "UPDATE actualizaciones_web 
                  SET estado = 'pendiente' 
                  WHERE :hoy BETWEEN mostrar_desde AND mostrar_hasta 
                  AND DATE(fecha_actualizacion) > :hoy 
                  AND estado != 'pendiente'";
$pdo->prepare($sqlPendientes)->execute([':hoy' => $hoy]);

echo json_encode(['success' => true, 'message' => 'Actualizaciones revisadas']);
