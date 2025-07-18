<?php
require_once '../vendor/autoload.php';
require_once '../database/conexion.php';
require_once __DIR__ . '/../middlewares/headers_post.php';
require_once __DIR__ . '/../controllers/rol/permisos/permisos.php';
require_once __DIR__ . '/../controllers/rol/permisos/validador_permisos.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$data = json_decode(file_get_contents('php://input'), true);
$usuarioId = $data['usuario_id'] ?? null;

if (!$usuarioId) {
	http_response_code(400);
	echo json_encode(["error" => "ID del usuario requerido"]);
	exit;
}

$tienePermisoGeneral = tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['EXPORTAR_TODOS']);
$tienePermisoPropios = tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['EXPORTAR_PROPIOS']);
$tienePermisoAsignados = tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['EXPORTAR_ASIGNADOS']);

if (!$tienePermisoGeneral && !$tienePermisoPropios && !$tienePermisoAsignados) {
	http_response_code(403);
	echo json_encode(["error" => "No tienes permisos para exportar mantenimientos"]);
	exit;
}

$stmtUser = $pdo->prepare("SELECT nombre_completo, correo, telefono FROM usuarios WHERE id = ?");
$stmtUser->execute([$usuarioId]);
$usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

header("Access-Control-Allow-Origin: https://departamento-sistemasips.vercel.app");
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header('Content-Disposition: attachment;filename="informe_mantenimientos.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$styleTitle = ['font' => ['bold' => true, 'size' => 18], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
$styleSubtitle = ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
$styleHeader = ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

$logoPath = realpath(__DIR__ . '/../public/logo.jpg');
if ($logoPath && file_exists($logoPath)) {
	$drawing = new Drawing();
	$drawing->setPath($logoPath);
	$drawing->setHeight(60);
	$drawing->setCoordinates('A1');
	$drawing->setWorksheet($sheet);
}

$sheet->mergeCells('B1:M1');
$sheet->setCellValue('B1', 'IPS CLINICAL HOUSE');
$sheet->getStyle('B1')->applyFromArray($styleTitle);
$sheet->getRowDimension('1')->setRowHeight(40);

$sheet->mergeCells('B2:M2');
$sheet->setCellValue('B2', 'Informe de Mantenimientos');
$sheet->getStyle('B2')->applyFromArray($styleSubtitle);
$sheet->getRowDimension('2')->setRowHeight(25);
$sheet->getRowDimension('3')->setRowHeight(10);

$headers = ['Título', 'Código', 'Modelo', 'Dependencia', 'Sede', 'Recibido por', 'Creado por', 'Fecha creación', 'Revisado por', 'Fecha revisión', 'Revisado?', 'Imagen', 'Descripción'];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:M4')->applyFromArray($styleHeader);

$sql = "
    SELECT 
        m.*, 
        s.nombre AS sede_nombre,
        u1.nombre_completo AS receptor_nombre,
        u2.nombre_completo AS creador_nombre,
        u3.nombre_completo AS revisor_nombre
    FROM mantenimientos m
    LEFT JOIN sedes s ON m.sede_id = s.id
    LEFT JOIN usuarios u1 ON m.nombre_receptor = u1.id
    LEFT JOIN usuarios u2 ON m.creado_por = u2.id
    LEFT JOIN usuarios u3 ON m.revisado_por = u3.id
";

$conditions = [];
$params = [];

if ($tienePermisoGeneral) {
	// No filtros
} elseif ($tienePermisoPropios) {
	$conditions[] = "m.creado_por = :usuario_id";
	$params['usuario_id'] = $usuarioId;
} elseif ($tienePermisoAsignados) {
	$conditions[] = "m.nombre_receptor = :usuario_id";
	$params['usuario_id'] = $usuarioId;
}

if (!empty($conditions)) {
	$sql .= " WHERE " . implode(" OR ", $conditions);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fila = 5;
foreach ($rows as $row) {
	$sheet->setCellValue("A{$fila}", $row['titulo']);
	$sheet->setCellValue("B{$fila}", $row['codigo']);
	$sheet->setCellValue("C{$fila}", $row['modelo']);
	$sheet->setCellValue("D{$fila}", $row['dependencia']);
	$sheet->setCellValue("E{$fila}", $row['sede_nombre']);
	$sheet->setCellValue("F{$fila}", $row['receptor_nombre']);
	$sheet->setCellValue("G{$fila}", $row['creador_nombre']);
	$sheet->setCellValue("H{$fila}", date('Y-m-d', strtotime($row['fecha_creacion'])));
	$sheet->setCellValue("I{$fila}", $row['revisor_nombre']);
	$sheet->setCellValue("J{$fila}", $row['fecha_revisado'] ? date('Y-m-d', strtotime($row['fecha_revisado'])) : '');
	$sheet->setCellValue("K{$fila}", $row['esta_revisado'] ? 'Sí' : 'No');

	$nombreImagen = trim($row['imagen'] ?? '');
	$rutaImagen = realpath(__DIR__ . '/../' . $nombreImagen);
	if (!empty($nombreImagen) && file_exists($rutaImagen)) {
		$dibujo = new Drawing();
		$dibujo->setPath($rutaImagen);
		$dibujo->setHeight(70);
		$dibujo->setCoordinates("L{$fila}");
		$dibujo->setWorksheet($sheet);
	} else {
		$sheet->setCellValue("L{$fila}", 'Sin imagen');
	}

	$sheet->setCellValue("M{$fila}", strip_tags($row['descripcion'] ?? ''));
	$sheet->getRowDimension($fila)->setRowHeight(80);
	$fila++;
}

$firmaInicio = $fila + 2;
$sheet->mergeCells("A{$firmaInicio}:E{$firmaInicio}");
$sheet->setCellValue("A{$firmaInicio}", "Firma del responsable:");
$sheet->getStyle("A{$firmaInicio}")->getFont()->setBold(true);

$sheet->mergeCells("A" . ($firmaInicio + 1) . ":E" . ($firmaInicio + 1));
$sheet->setCellValue("A" . ($firmaInicio + 1), "Nombre: " . $usuario['nombre_completo']);
$sheet->mergeCells("A" . ($firmaInicio + 2) . ":E" . ($firmaInicio + 2));
$sheet->setCellValue("A" . ($firmaInicio + 2), "Correo: " . $usuario['correo']);
$sheet->mergeCells("A" . ($firmaInicio + 3) . ":E" . ($firmaInicio + 3));
$sheet->setCellValue("A" . ($firmaInicio + 3), "Teléfono: " . $usuario['telefono']);

foreach (range('A', 'M') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
