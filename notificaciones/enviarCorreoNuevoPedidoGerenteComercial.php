<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// esta funcion es para enviar correo las personas que tiene permiso de gestionar el pedido
function enviarCorreoNuevoPedidoGerenteComercial(
    $paraCorreo,
    $nombreUsuario,
    $fechaSolicitud,
    $procesoSolicitante,
    $tipoSolicitud,
    $observacion,
    $consecutivo = null
) {
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
        $tipoTexto = [
            '1' => 'Recurrente',
            '2' => 'Prioritaria'
        ][$tipoSolicitud] ?? 'No especificado';

        $colorTipo = $tipoSolicitud == '2' ? '#EF4444' : '#3B82F6';
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
        $mail->Subject = '📊 Pedido pendiente de revisión ' . ($consecutivo ? "#$consecutivo" : '');

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuevo Pedido</title>
<style>
    body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #374151; background-color: #f3f4f6; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #15803d, #166534); color: white; padding: 30px; text-align: center; }
    .content { padding: 30px; }
    .badge { display: inline-block; background-color: {$colorTipo}; color: white; font-size: 14px; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin-bottom: 20px; }
    .card { background: #f8fafc; border-radius: 12px; padding: 25px; margin: 25px 0; border-left: 5px solid {$colorTipo}; }
    .detail-grid { display: grid; grid-template-columns: 120px 1fr; gap: 12px; margin-bottom: 12px; }
    .detail-label { font-weight: 500; color: #64748b; }
    .detail-value { font-weight: 500; color: #1e293b; }
    .button-container { text-align: center; margin: 30px 0 20px; }
    .button { display: inline-block; background: linear-gradient(135deg, #15803d, #166534); color: white !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 500; }
    .footer { padding: 20px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .observacion { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px; font-style: italic; color: #475569; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📌 Pedido pendiente de aprobación</h1>
    </div>
    <div class="content">
        <div class="badge">{$tipoTexto}</div>
        <p>Estimado/a <strong>{$nombreUsuario}</strong>,</p>
        <p>Se ha registrado un nuevo pedido en el sistema y está a la espera de su revisión y aprobación.</p>
        <p>Por favor verifique la siguiente información para decidir si procede con su gestión:</p>
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
