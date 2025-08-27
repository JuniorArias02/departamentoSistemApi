<?php
function obtenerInfoSesion() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconocida';
    $fecha = date('Y-m-d H:i:s');

    // Detectar navegador/equipo
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Agente desconocido';

    return [
        'ip' => $ip,
        'fecha' => $fecha,
        'user_agent' => $userAgent
    ];
}
