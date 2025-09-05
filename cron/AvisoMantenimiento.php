<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


function enviarCorreoCancelarMantenimiento($paraCorreo, $nombreTecnico)
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

		$mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificación de Equipos</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Inter', Arial, sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      margin: 0;
      padding: 20px;
      color: #334155;
    }
    
    .container {
      max-width: 700px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      border: 1px solid #e2e8f0;
    }
    
    .header {
      background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
      color: white;
      padding: 25px;
      text-align: center;
      position: relative;
    }
    
    .header-content {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
    }
    
    .header-icon {
      background: rgba(255, 255, 255, 0.2);
      padding: 12px;
      border-radius: 12px;
      backdrop-filter: blur(10px);
    }
    
    .header h1 {
      margin: 0;
      font-size: 24px;
      font-weight: 600;
      letter-spacing: -0.5px;
    }
    
    .header p {
      margin: 8px 0 0;
      opacity: 0.9;
      font-size: 14px;
    }
    
    .content {
      padding: 30px;
    }
    
    .summary {
      background: #f8fafc;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      border-left: 4px solid #2563eb;
    }
    
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 15px;
      margin-top: 15px;
    }
    
    .summary-item {
      text-align: center;
      padding: 15px;
      border-radius: 10px;
      background: white;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .summary-number {
      font-size: 24px;
      font-weight: 700;
      color: #2563eb;
      margin-bottom: 5px;
    }
    
    .summary-label {
      font-size: 12px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .equipo {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      border: 1px solid #e2e8f0;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .equipo:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .equipo-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }
    
    .equipo h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    
    .badge.ok { 
      background: #dcfce7; 
      color: #166534; 
      border: 1px solid #bbf7d0;
    }
    
    .badge.alerta { 
      background: #fef3c7; 
      color: #92400e; 
      border: 1px solid #fde68a;
    }
    
    .badge.vencido { 
      background: #fee2e2; 
      color: #991b1b; 
      border: 1px solid #fecaca;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-top: 15px;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .info-label {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 500;
    }
    
    .info-value {
      font-size: 14px;
      color: #1e293b;
      font-weight: 500;
    }
    
    .progress-bar {
      height: 6px;
      background: #e2e8f0;
      border-radius: 3px;
      margin-top: 10px;
      overflow: hidden;
    }
    
    .progress-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 0.3s ease;
    }
    
    .progress-fill.ok { background: #16a34a; width: 90%; }
    .progress-fill.alerta { background: #f59e0b; width: 30%; }
    .progress-fill.vencido { background: #dc2626; width: 0%; }
    
    .footer {
      background: #f8fafc;
      padding: 25px;
      text-align: center;
      border-top: 1px solid #e2e8f0;
    }
    
    .footer p {
      margin: 0;
      font-size: 12px;
      color: #64748b;
      line-height: 1.6;
    }
    
    .button {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 20px;
      background: #2563eb;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      transition: background 0.2s ease;
    }
    
    .button:hover {
      background: #1e40af;
    }
    
    @media (max-width: 600px) {
      .container {
        margin: 20px auto;
        border-radius: 12px;
      }
      
      .header {
        padding: 20px;
      }
      
      .header h1 {
        font-size: 20px;
      }
      
      .content {
        padding: 20px;
      }
      
      .info-grid {
        grid-template-columns: 1fr;
      }
      
      .summary-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="header-content">
        <div class="header-icon">🔔</div>
        <div>
          <h1>Notificación de Mantenimientos</h1>
          <p>Estado de los equipos que requieren atención</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <!-- Resumen -->
      <div class="summary">
        <h3 style="margin: 0; color: #1e293b; font-weight: 600;">Resumen del Estado</h3>
        <div class="summary-grid">
          <div class="summary-item">
            <div class="summary-number">2</div>
            <div class="summary-label">Equipos Totales</div>
          </div>
          <div class="summary-item">
            <div class="summary-number">1</div>
            <div class="summary-label">Por Vencer</div>
          </div>
          <div class="summary-item">
            <div class="summary-number">1</div>
            <div class="summary-label">Vencidos</div>
          </div>
        </div>
      </div>

      <!-- Equipos -->
      <div class="equipo">
        <div class="equipo-header">
          <h2>PC Oficina - Dell Inspiron</h2>
          <span class="badge alerta">⏳ Próximo a vencer</span>
        </div>
        
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Responsable</span>
            <span class="info-value">Juan Pérez</span>
          </div>
          <div class="info-item">
            <span class="info-label">Área / Sede</span>
            <span class="info-value">Contabilidad | Cúcuta</span>
          </div>
          <div class="info-item">
            <span class="info-label">Serial / Inventario</span>
            <span class="info-value">ABC123456 | 00045</span>
          </div>
          <div class="info-item">
            <span class="info-label">Último Mantenimiento</span>
            <span class="info-value">10 Jun 2025</span>
          </div>
          <div class="info-item">
            <span class="info-label">Días Restantes</span>
            <span class="info-value" style="color: #f59e0b;">15 días</span>
          </div>
        </div>
        
        <div class="progress-bar">
          <div class="progress-fill alerta"></div>
        </div>
      </div>

      <div class="equipo">
        <div class="equipo-header">
          <h2>Laptop HP - EliteBook</h2>
          <span class="badge vencido">⚠️ Vencido</span>
        </div>
        
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Responsable</span>
            <span class="info-value">María Gómez</span>
          </div>
          <div class="info-item">
            <span class="info-label">Área / Sede</span>
            <span class="info-value">Sistemas | Bogotá</span>
          </div>
          <div class="info-item">
            <span class="info-label">Serial / Inventario</span>
            <span class="info-value">XYZ987654 | 00120</span>
          </div>
          <div class="info-item">
            <span class="info-label">Último Mantenimiento</span>
            <span class="info-value">01 Jul 2025</span>
          </div>
          <div class="info-item">
            <span class="info-label">Días de Retraso</span>
            <span class="info-value" style="color: #dc2626;">3 días</span>
          </div>
        </div>
        
        <div class="progress-bar">
          <div class="progress-fill vencido"></div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>
        <strong>© 2025 - Sistema de Gestión de Equipos</strong><br>
        Este es un mensaje automático, por favor no responder directamente.<br>
        Para más información contacte al área de Sistemas.
      </p>
      <a href="#" class="button">Acceder al Sistema</a>
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


enviarCorreoCancelarMantenimiento("junior.arias02yt@gmail.com", "Junior Arias");