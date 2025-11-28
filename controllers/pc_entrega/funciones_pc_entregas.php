<?php

function validarCampos($input, $requeridos) {
	$faltantes = [];
	foreach ($requeridos as $campo) {
		if (!isset($input[$campo]) || $input[$campo] === "") {
			$faltantes[] = $campo;
		}
	}
	return $faltantes;
}

function guardarFirmaBase64($base64_string, $dan = "firma") {
	$directorio = __DIR__ . '/../../public/firmas/';
	if (!file_exists($directorio)) {
		mkdir($directorio, 0755, true);
	}

	if (preg_match('/^data:image\/png;base64,/', $base64_string)) {
		$base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
	}
	$base64_string = str_replace(' ', '+', $base64_string);
	$data = base64_decode($base64_string);

	if ($data === false) return null;

	$nombre = uniqid($dan . "_") . ".png";
	$ruta = $directorio . $nombre;

	if (file_put_contents($ruta, $data)) {
		return "public/firmas/" . $nombre;
	}
	return null;
}

function insertarActa($pdo, $input, $firma_entrega, $firma_recibe) {
	$stmt = $pdo->prepare("
		INSERT INTO pc_entregas (
			equipo_id, funcionario_id, fecha_entrega, firma_entrega, firma_recibe
		) VALUES (
			:equipo_id, :funcionario_id, :fecha_entrega, :firma_entrega, :firma_recibe
		)
	");
	$stmt->execute([
		"equipo_id"      => $input["equipo_id"],
		"funcionario_id" => $input["funcionario_id"],
		"fecha_entrega"  => $input["fecha_entrega"],
		"firma_entrega"  => $firma_entrega,
		"firma_recibe"   => $firma_recibe,
	]);
	return $pdo->lastInsertId();
}

function actualizarResponsableEquipo($pdo, $input) {
	$stmt = $pdo->prepare("
		UPDATE pc_equipos
		SET responsable_id = :responsable_id, fecha_entrega = :fecha_entrega
		WHERE id = :equipo_id
	");
	$stmt->execute([
		"responsable_id" => $input["funcionario_id"],
		"fecha_entrega"  => $input["fecha_entrega"],
		"equipo_id"      => $input["equipo_id"]
	]);
}

function insertarPerifericos($pdo, $entrega_id, $perifericos) {
	if (!empty($perifericos) && is_array($perifericos)) {
		$stmt = $pdo->prepare("
			INSERT INTO pc_perifericos_entregados (
				entrega_id, inventario_id, cantidad, observaciones
			) VALUES (
				:entrega_id, :inventario_id, :cantidad, :observaciones
			)
		");
		foreach ($perifericos as $p) {
			if (!empty($p["inventario_id"])) {
				$stmt->execute([
					"entrega_id"     => $entrega_id,
					"inventario_id"  => $p["inventario_id"],
					"cantidad"       => $p["cantidad"] ?? 1,
					"observaciones"  => $p["observaciones"] ?? null,
				]);
			}
		}
	}
}
