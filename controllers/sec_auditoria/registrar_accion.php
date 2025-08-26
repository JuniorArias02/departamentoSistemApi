<?php
require_once __DIR__ . '/../../database/conexion.php';

function registrar_accion($pdo, $id_usuario, $accion, $detalle = null, $ip = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sec_auditoria (id_usuario, accion, detalle, ip, fecha)
            VALUES (:id_usuario, :accion, :detalle, :ip, NOW())
        ");
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);
        $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar acción: " . $e->getMessage());
        return false;
    }
}
