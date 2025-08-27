<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function enviarCorreoNotificacionLogin($paraCorreo, $nombreUsuario, $ip, $fecha, $equipo)
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
        $mail->Subject = "🔐 Nuevo inicio de sesión detectado";

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de seguridad</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        
        .header h1 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }
        
        .content {
            padding: 30px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #444;
        }
        
        .alert-box {
            background-color: #fff4f4;
            border-left: 4px solid #ff5252;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .info-card {
            background-color: #f9fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-item {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            width: 24px;
            margin-right: 12px;
            color: #667eea;
            font-size: 18px;
        }
        
        .info-label {
            font-weight: 500;
            min-width: 100px;
            color: #555;
        }
        
        .info-value {
            color: #333;
            font-weight: 400;
        }
        
        .warning {
            background-color: #fff8e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        
        .footer {
            background-color: #f5f7fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
            color: #777;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 20px;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Notificación de Seguridad</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hola, <strong>{$nombreUsuario}</strong>
            </div>
            
            <div class="alert-box">
                <strong>Se ha detectado un nuevo inicio de sesión en tu cuenta.</strong>
            </div>
            
            <div class="info-card">
                <div class="info-item">
                    <div class="info-icon">📅</div>
                    <div class="info-label">Fecha:</div>
                    <div class="info-value">{$fecha}</div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🌐</div>
                    <div class="info-label">Dirección IP:</div>
                    <div class="info-value">{$ip}</div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">💻</div>
                    <div class="info-label">Dispositivo:</div>
                    <div class="info-value">{$equipo}</div>
                </div>
            </div>
            
            <div class="warning">
                <strong>¿No reconoces esta actividad?</strong>
                <p>Si no fuiste tú quien inició sesión, te recomendamos cambiar tu contraseña de inmediato y revisar la seguridad de tu cuenta.</p>
            </div>
            
            <p>Si tienes alguna pregunta, no dudes en contactar a nuestro equipo de soporte.</p>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
        </div>
    </div>
</body>
</html>
HTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}