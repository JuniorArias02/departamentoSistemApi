<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Dotenv\Dotenv;


require_once __DIR__ . '/../vendor/autoload.php';



$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

var_dump(getenv('MAIL_HOST'));

function enviarCorreoMantenimientoAgendado($paraCorreo, $nombreTecnico, $titulo, $descripcion, $fechaInicio, $fechaFin, $sedeNombre)
{

	$mail = new PHPMailer(true);

	try {
		// Configuración del servidor SMTP
		$mail->isSMTP();
		$mail->Host       = $_ENV['MAIL_HOST'];
		$mail->SMTPAuth   = true;
		$mail->Username   = $_ENV['MAIL_USERNAME'];
		$mail->Password   = $_ENV['MAIL_PASSWORD'];
		$mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
		$mail->Port       = $_ENV['MAIL_PORT'];

		// Remitente y destinatario
		$mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_NAME']);
		$mail->addAddress($paraCorreo, $nombreTecnico);

		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';

		// Contenido del correo
		$mail->isHTML(true);
		$mail->Subject = '📅 Nuevo Mantenimiento Agendado';
		$mail->Body    = "
		<!DOCTYPE html>
		<html>
		<head>
			<style>
				body {
					font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
					line-height: 1.6;
					color: #333;
					max-width: 600px;
					margin: 0 auto;
					padding: 0;
					background-color: #f5f5f5;
				}
				.header {
					background-color: #5E35B1; /* Morado/Azul vibrante de tu web */
					color: white;
					padding: 25px;
					text-align: center;
					border-radius: 5px 5px 0 0;
				}
				.header h1 {
				color:white;
				}
				.content {
					padding: 25px;
					background-color: white;
					border: 1px solid #e1e1e1;
					border-top: none;
					border-radius: 0 0 5px 5px;
				}
				h1 {
					color: #5E35B1; /* Mismo morado que el header */
					margin-top: 0;
					font-size: 24px;
				}
				h2 {
					color: #333;
					font-size: 20px;
				}
				.details {
					background-color: #f9f9f9;
					padding: 20px;
					border-radius: 5px;
					margin: 20px 0;
					border-left: 4px solid #FB8C00; /* Acento naranja */
				}
				.details li {
					margin-bottom: 10px;
					list-style-type: none;
					padding-left: 5px;
				}
				.footer {
					margin-top: 25px;
					font-size: 12px;
					color: #777;
					text-align: center;
					border-top: 1px solid #eee;
					padding-top: 15px;
				}
				.highlight {
					color: #FB8C00; /* Naranja exacto de tu web */
					font-weight: 600;
				}
				strong {
					color: #5E35B1; /* Morado para los labels */
				}
			</style>
		</head>
		<body>
			<div class='header'>
				<h1>📅 Nuevo Mantenimiento Agendado</h1>
			</div>
			
			<div class='content'>
				<h2>Hola <span class='highlight'>$nombreTecnico</span>,</h2>
				<p>Se ha agendado un nuevo mantenimiento en el sistema:</p>
				
				<div class='details'>
					<ul>
						<li><strong>Título:</strong> $titulo</li>
						<li><strong>Descripción:</strong> $descripcion</li>
						<li><strong>Sede:</strong> <span class='highlight'>$sedeNombre</span></li>
						<li><strong>Fecha de inicio:</strong> $fechaInicio</li>
						<li><strong>Fecha de finalización:</strong> $fechaFin</li>
					</ul>
				</div>
				
				<p>Por favor revisa tu agenda y prepara los recursos necesarios.</p>
				<p>¡Gracias por tu atención!</p>
				
				<div class='footer'>
					<p><strong style='color: #5E35B1;'>Departamento de Sistemas IPS</strong></p>
					<p>Este es un mensaje automático, por favor no respondas a este correo.</p>
				</div>
			</div>
		</body>
		</html>
		";

		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Error al enviar correo: {$mail->ErrorInfo}");
		return false;
	}
}
