<?php
// require_once __DIR__ . '/enviarCorreoMantenimientoAgendado.php';
require_once "./enviarCorreoMantenimientoAgendado.php";

enviarCorreoMantenimientoAgendado(
	"junior.arias02yt@gmail.com",
	"Junior Arias",
	"Prueba de título",
	"Esta es una prueba de descripción",
	"2025-07-20 10:00:00",
	"2025-07-20 11:00:00",
	"Sede de prueba"
);
