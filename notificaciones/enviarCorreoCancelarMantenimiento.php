<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Dotenv\Dotenv;


require_once __DIR__ . '/../vendor/autoload.php';



$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function enviarCorreoCancelarMantenimiento($paraCorreo, $nombreTecnico, $titulo, $descripcion, $fechaInicio, $fechaFin, $sedeNombre)
{
	$mail = new PHPMailer(true);

	try {
		$mail->isSMTP();
		$mail->Host       = $_ENV['MAIL_HOST'];
		$mail->SMTPAuth   = true;
		$mail->Username   = $_ENV['MAIL_USERNAME'];
		$mail->Password   = $_ENV['MAIL_PASSWORD'];
		$mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
		$mail->Port       = $_ENV['MAIL_PORT'];

		$mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_NAME']);
		$mail->addAddress($paraCorreo, $nombreTecnico);

		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';
		$mail->isHTML(true);
		$mail->Subject = '❌ Cancelación de Mantenimiento Programado';

		$mail->Body = "
		<!DOCTYPE html>
		<html>
		<head>
			<style>
				body {
					font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
					color: #333;
					background-color: #f9f9f9;
					padding: 0;
					margin: 0;
				}
				.container {
					max-width: 600px;
					margin: 30px auto;
					background-color: white;
					border-radius: 8px;
					box-shadow: 0 0 10px rgba(0,0,0,0.1);
					overflow: hidden;
				}
				.header {
					background-color: #d32f2f;
					color: white;
					padding: 20px;
					text-align: center;
				}
				.content {
					padding: 20px;
				}
				.details {
					background-color: #f1f1f1;
					border-left: 5px solid #d32f2f;
					padding: 15px;
					margin-top: 15px;
					border-radius: 5px;
				}
				.details li {
					margin: 8px 0;
					list-style: none;
				}
				.footer {
					font-size: 12px;
					text-align: center;
					color: #777;
					padding: 15px;
				}
			</style>
		</head>
		<body>
			<div class='container'>
				<div class='header'>
					<h1>Cancelación de Mantenimiento</h1>
				</div>
				<div class='content'>
					<p>Hola <strong>$nombreTecnico</strong>,</p>
					<p>Lamentamos informarte que el siguiente mantenimiento ha sido cancelado. Pedimos disculpas por cualquier inconveniente que esto pueda causar.</p>
					
					<div class='details'>
						<ul>
							<li><strong>Título:</strong> $titulo</li>
							<li><strong>Descripción:</strong> $descripcion</li>
							<li><strong>Sede:</strong> $sedeNombre</li>
							<li><strong>Fecha de inicio:</strong> $fechaInicio</li>
							<li><strong>Fecha de finalización:</strong> $fechaFin</li>
						</ul>
					</div>

					<p>Gracias por tu comprensión.</p>
					<p>— Departamento de Sistemas</p>
				</div>
				<div class='footer'>
					<p>Este es un mensaje automático, por favor no responder directamente a este correo.</p>
				</div>
			</div>
		</body>
		</html>";

		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Error al enviar correo: {$mail->ErrorInfo}");
		return false;
	}
}
