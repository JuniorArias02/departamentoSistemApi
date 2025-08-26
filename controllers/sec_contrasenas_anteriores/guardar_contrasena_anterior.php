<?php
function guardar_contrasena_anterior($pdo, $id_usuario, $contrasena_actual)
{
	$stmt = $pdo->prepare("
        INSERT INTO sec_contrasenas_anteriores (id_usuario, contrasena, fecha_guardada)
        VALUES (:id_usuario, :contrasena, NOW())
    ");
	$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
	$stmt->bindParam(':contrasena', $contrasena_actual, PDO::PARAM_STR);
	return $stmt->execute();
}
