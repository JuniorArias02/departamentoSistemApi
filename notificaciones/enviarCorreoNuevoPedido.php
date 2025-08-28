<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function enviarCorreoNuevoPedido($paraCorreo, $nombreUsuario, $fechaSolicitud, $procesoSolicitante, $tipoSolicitud, $observacion, $consecutivo = null)
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
        $mail->addAddress($paraCorreo, $nombreUsuario);

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Datos base
        // Datos base
        $tipoTexto = htmlspecialchars($tipoSolicitud);
        $colorTipo = match (strtolower($tipoTexto)) {
            'Prioritaria' => '#EF4444',   // rojo
            'Recurrente'     => '#F59E0B',   // naranja
            default       => '#3B82F6',   // azul
        };

        
        $numeroPedido = $consecutivo ? "#$consecutivo" : 'Nuevo';
        $appUrl = $_ENV['APP_URL'] ?? 'https://departamento-sistemasips.vercel.app/dashboard/compras';

        // Bloque observación
        $htmlObservacion = '';
        if (!empty($observacion)) {
            $htmlObservacion = '
                <div style="margin-top: 15px;">
                    <div class="detail-label">Observaciones:</div>
                    <div class="observacion">' . htmlspecialchars($observacion) . '</div>
                </div>';
        }

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '✨ Nuevo Pedido ' . ($consecutivo ? "#$consecutivo" : '');

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuevo Pedido</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #374151; background-color: #f3f4f6; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #1e40af, #1e3a8a); color: white; padding: 30px; text-align: center; position: relative; }
    .header-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('https://departamento-sistemasips.vercel.app/android-chrome-512x512.png') no-repeat center right -50px; background-size: 150px; opacity: 0.1; }
    .header-content { position: relative; z-index: 1; }
    .header h1 { margin: 0; font-size: 24px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 12px; }
    .logo { height: 40px; margin-bottom: 15px; }
    .content { padding: 30px; }
    .badge { display: inline-block; background-color: {$colorTipo}; color: white; font-size: 14px; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin-bottom: 20px; }
    .card { background: #f8fafc; border-radius: 12px; padding: 25px; margin: 25px 0; border-left: 5px solid {$colorTipo}; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .detail-grid { display: grid; grid-template-columns: 120px 1fr; gap: 12px; margin-bottom: 12px; }
    .detail-label { font-weight: 500; color: #64748b; }
    .detail-value { font-weight: 500; color: #1e293b; }
    .button-container { text-align: center; margin: 30px 0 20px; }
    .button { display: inline-block; background: linear-gradient(135deg, #1e40af, #1e3a8a); color: white !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s ease; }
    .button:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .footer { padding: 20px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .observacion { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px; font-style: italic; color: #475569; }
    @media (max-width: 600px) { .container { margin: 0; border-radius: 0; } .content { padding: 20px; } .detail-grid { grid-template-columns: 1fr; gap: 6px; } .header h1 { font-size: 20px; } }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-overlay"></div>
        <div class="header-content">
            <img src="https://departamento-sistemasips.vercel.app/android-chrome-512x512.png" alt="Logo" class="logo">
            <h1>Nuevo Pedido Registrado</h1>
        </div>
    </div>
    <div class="content">
        <div class="badge">{$tipoTexto}</div>
        <p>Hola <strong>{$nombreUsuario}</strong>,</p>
        <p>Se ha registrado un nuevo pedido en el sistema que requiere tu atención:</p>
        <div class="card">
            <div class="detail-grid">
                <div class="detail-label">Número:</div>
                <div class="detail-value">{$numeroPedido}</div>
            </div>
            <div class="detail-grid">
                <div class="detail-label">Fecha:</div>
                <div class="detail-value">{$fechaSolicitud}</div>
            </div>
            <div class="detail-grid">
                <div class="detail-label">Solicitante:</div>
                <div class="detail-value">{$procesoSolicitante}</div>
            </div>
            <div class="detail-grid">
                <div class="detail-label">Tipo:</div>
                <div class="detail-value" style="color: {$colorTipo}; font-weight: 600;">{$tipoTexto}</div>
            </div>
            {$htmlObservacion}
        </div>
        <p style="margin-top: 10px; color: #64748b; font-size: 14px; text-align: center;">
            Este es un mensaje automático. Por favor no respondas directamente a este correo.
        </p>
    </div>
    <div class="footer">
        <p>© {date('Y')} Departamento de Sistemas - IPS. Todos los derechos reservados.</p>
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
