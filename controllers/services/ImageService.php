<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService {
    private static $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    public static function uploadAndCompressImage(
        string $base64Data, 
        string $uploadDir,
        int $maxWidth = 1024,
        int $quality = 70
    ): array {
        // 1. Validar tamaño ANTES de decodificar (aumentado a 90MB)
        $headerSize = strpos($base64Data, ',') + 1;
        $encodedData = substr($base64Data, $headerSize);
        $dataLength = strlen($encodedData) * 3 / 4; // Tamaño aproximado
        
        if ($dataLength > 90 * 1024 * 1024) { // 90MB
            throw new RuntimeException("La imagen excede el límite de 90MB");
        }

        // 2. Validar tipo (agregado gif)
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            throw new InvalidArgumentException("Formato base64 inválido");
        }

        $mimeType = 'image/' . $matches[1];
        $extension = self::$allowedMimeTypes[$mimeType] ?? null;

        if (!$extension) {
            throw new DomainException("Tipo de imagen no permitido. Formatos válidos: jpg, png, webp, gif");
        }

        // 3. Usar archivo temporal para evitar cargar en memoria
        $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.' . $extension;
        try {
            // Decodificar directamente a archivo
            $decoded = base64_decode($encodedData);
            if ($decoded === false) {
                throw new RuntimeException("Error al decodificar imagen");
            }
            
            file_put_contents($tempFile, $decoded);
            
            // 4. Procesar con Intervention Image (optimizado para grandes archivos)
            $manager = new ImageManager(new Driver());
            $image = $manager->read($tempFile);
            
            // Redimensionar si es necesario (solo para imágenes no GIF)
            if ($mimeType !== 'image/gif' && $image->width() > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            // Configuración de calidad basada en tamaño original
            $originalSizeMB = $dataLength / (1024 * 1024);
            
            // Ajustar calidad automáticamente
            if ($originalSizeMB > 20) {
                $quality = 60; // Más compresión para imágenes grandes
            } elseif ($originalSizeMB > 10) {
                $quality = 70;
            } else {
                $quality = 80; // Mejor calidad para imágenes pequeñas
            }

            // Guardar resultado
            $filename = uniqid('img_') . '.' . $extension;
            $fullPath = rtrim($uploadDir, '/') . '/' . $filename;
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // No comprimir GIFs para mantener la animación
            if ($mimeType === 'image/gif') {
                copy($tempFile, $fullPath);
            } else {
                $image->save($fullPath, quality: $quality);
            }

            return [
                'path' => $fullPath,
                'public_url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $fullPath),
                'size' => filesize($fullPath),
                'original_size' => strlen($decoded),
                'compressed' => ($mimeType !== 'image/gif'),
                'quality_applied' => ($mimeType !== 'image/gif') ? $quality : 'original'
            ];
            
        } finally {
            // Limpieza garantizada
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}