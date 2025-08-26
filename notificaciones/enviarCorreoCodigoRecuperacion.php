<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function enviarCorreoCodigoRecuperacion($paraCorreo, $nombreUsuario, $codigo)
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
		$mail->addAddress($paraCorreo, $nombreUsuario);

		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';
		$mail->isHTML(true);
		$mail->Subject = "Código de recuperación de contraseña";

		$mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Código de recuperación</title>
        </head>
        <body>
            <p>Hola <strong>{$nombreUsuario}</strong>,</p>
            <p>Has solicitado recuperar tu contraseña. Tu código de verificación es:</p>
            <h2 style='color: #2E86C1;'>{$codigo}</h2>
            <p>Este código es válido por 1 día. No lo compartas con nadie.</p>
            <br>
            <p>Si no solicitaste este código, ignora este correo.</p>
        </body>
        </html>";

		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Error al enviar correo: {$mail->ErrorInfo}");
		return false;
	}
}
