<?php
function calcularTiempoRestante($expiracion)
{
	$exp = new DateTime($expiracion, new DateTimeZone("America/Bogota")); // Ajusta tu zona horaria
	$now = new DateTime("now", new DateTimeZone("America/Bogota"));
	$diff = $exp->getTimestamp() - $now->getTimestamp();
	return max(0, $diff);
}
