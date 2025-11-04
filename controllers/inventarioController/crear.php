<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

date_default_timezone_set('America/Bogota');

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$campos = ['codigo', 'nombre', 'creado_por'];

foreach ($campos as $campo) {
    if (!isset($data[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
        exit;
    }
    if (trim($data[$campo]) === '') {
        http_response_code(400);
        echo json_encode(["error" => "El campo '$campo' no puede estar vacío."]);
        exit;
    }
}
$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("debug_data.log", print_r($data, true));


function limpiarNumero($valor)
{
    $valor = str_replace(',', '.', $valor);
    return is_numeric($valor) ? $valor : null;
}



try {
    $fechaColombia = date('Y-m-d H:i:s');

    if (!empty($data['id'])) {
        // EDITAR INVENTARIO
        $stmt = $pdo->prepare("UPDATE inventario SET 
            codigo = :codigo,
            nombre = :nombre,
            dependencia = :dependencia,
            responsable = :responsable,
            marca = :marca,
            modelo = :modelo,
            serial = :serial,
            sede_id = :sede_id,
            codigo_barras = :codigo_barras,
            grupo = :grupo,
            vida_util = :vida_util,
            vida_util_niff = :vida_util_niff,
            centro_costo = :centro_costo,
            ubicacion = :ubicacion,
            proveedor = :proveedor,
            fecha_compra = :fecha_compra,
            soporte = :soporte,
            descripcion = :descripcion,
            estado = :estado,
            escritura = :escritura,
            matricula = :matricula,
            valor_compra = :valor_compra,
            salvamenta = :salvamenta,
            depreciacion = :depreciacion,
            depreciacion_niif = :depreciacion_niif,
            meses = :meses,
            meses_niif = :meses_niif,
            tipo_adquisicion = :tipo_adquisicion,
            calibrado = :calibrado,
            observaciones = :observaciones,
            fecha_actualizacion = NOW()
            WHERE id = :id");

        if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['INVENTARIO']['EDITAR'])) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Acceso denegado. No tienes permiso para editar inventario."
            ]);
            exit();
        }

        $stmt->execute([
            "codigo" => $data["codigo"],
            "nombre" => $data["nombre"],
            "dependencia" => $data["dependencia"] ?? null,
            "responsable" => $data["responsable"] ?? null,
            "marca" => $data["marca"] ?? null,
            "modelo" => $data["modelo"] ?? null,
            "serial" => $data["serial"] ?? null,
            "sede_id" => $data["sede_id"] ?? null,
            "codigo_barras" => $data["codigo_barras"] ?? null,
            "grupo" => $data["grupo"] ?? null,
            "vida_util" => !empty($data["vida_util"]) ? $data["vida_util"] : null,
            "vida_util_niff" => !empty($data["vida_util_niff"]) ? $data["vida_util_niff"] : null,
            "centro_costo" => $data["centro_costo"] ?? null,
            "ubicacion" => $data["ubicacion"] ?? null,
            "proveedor" => $data["proveedor"] ?? null,
            "fecha_compra" => !empty($data["fecha_compra"]) ? $data["fecha_compra"] : null,
            "soporte" => $data["soporte"] ?? null,
            "descripcion" => $data["descripcion"] ?? null,
            "estado" => $data["estado"] ?? null,
            "escritura" => $data["escritura"] ?? null,
            "matricula" => $data["matricula"] ?? null,
            "valor_compra" => limpiarNumero($data["valor_compra"] ?? null),
            "salvamenta" => $data["salvamenta"] ?? null,
            "depreciacion" => limpiarNumero($data["depreciacion"] ?? null),
            "depreciacion_niif" => limpiarNumero($data["depreciacion_niif"] ?? null),
            "meses" => $data["meses"] ?? null,
            "meses_niif" => $data["meses_niif"] ?? null,
            "tipo_adquisicion" => $data["tipo_adquisicion"] ?? null,
            "calibrado" => !empty($data["calibrado"]) ? $data["calibrado"] : null,
            "observaciones" => $data["observaciones"] ?? null,
            "id" => $data["id"]
        ]);


        $fechaColombia = date('Y-m-d H:i:s');


        // REGISTRAR ACTIVIDAD DE ACTUALIZACIÓN
        registrarActividad(
            $pdo,
            $data['creado_por'],
            "Actualizo el inventario con código {$data['codigo']}",
            "inventario",
            $data['id']
        );

        // Traer datos actualizados (parte existente)
        $stmt2 = $pdo->prepare("SELECT * FROM inventario WHERE id = :id");
        $stmt2->execute(["id" => $data["id"]]);
        $registro = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "msg" => "Inventario actualizado con éxito",
            "data" => $registro
        ]);
    } else {
        // Verificar si el código ya existe, excepto si es "N/A"
        if (strtoupper(trim($data["codigo"])) !== "N/A") {
            $verificar = $pdo->prepare("SELECT id FROM inventario WHERE codigo = :codigo");
            $verificar->execute(["codigo" => $data["codigo"]]);
            if ($verificar->fetch()) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Ya existe un inventario con el código '{$data["codigo"]}'."
                ]);
                exit();
            }
        }

        // CREAR NUEVO INVENTARIO
        $stmt = $pdo->prepare("INSERT INTO inventario 
            (codigo, nombre, dependencia, responsable, marca, modelo, serial, sede_id, creado_por,
             codigo_barras, grupo, vida_util, vida_util_niff, centro_costo, ubicacion, proveedor,
             fecha_compra, soporte, descripcion, estado, escritura, matricula, valor_compra,
             salvamenta, depreciacion, depreciacion_niif, meses, meses_niif, tipo_adquisicion,
             calibrado, observaciones)
            VALUES 
            (:codigo, :nombre, :dependencia, :responsable, :marca, :modelo, :serial, :sede_id, :creado_por,
             :codigo_barras, :grupo, :vida_util, :vida_util_niff, :centro_costo, :ubicacion, :proveedor,
             :fecha_compra, :soporte, :descripcion, :estado, :escritura, :matricula, :valor_compra,
             :salvamenta, :depreciacion, :depreciacion_niif, :meses, :meses_niif, :tipo_adquisicion,
             :calibrado, :observaciones)");


        $valorCompra = str_replace(',', '.', $data["valor_compra"]);
        $depreciacion = str_replace(',', '.', $data["depreciacion"]);

        if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['INVENTARIO']['CREAR'])) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Acceso denegado. No tienes permiso para crear inventario."
            ]);
            exit();
        }

        $stmt->execute([
            "codigo" => $data["codigo"],
            "nombre" => $data["nombre"],
            "dependencia" => $data["dependencia"] ?? null,
            "responsable" => $data["responsable"] ?? null,
            "marca" => $data["marca"] ?? null,
            "modelo" => $data["modelo"] ?? null,
            "serial" => $data["serial"] ?? null,
            "sede_id" => $data["sede_id"] ?? null,
            "creado_por" => $data["creado_por"],
            "codigo_barras" => $data["codigo_barras"] ?? null,
            "grupo" => $data["grupo"] ?? null,
            "vida_util" => $data["vida_util"] ? $data["vida_util"] : null,
            "vida_util_niff" => $data["vida_util_niff"] ? $data["vida_util_niff"] : null,
            "centro_costo" => $data["centro_costo"] ?? null,
            "ubicacion" => $data["ubicacion"] ?? null,
            "proveedor" => $data["proveedor"] ?? null,
            "fecha_compra" => (!empty($data["fecha_compra"]) ? $data["fecha_compra"] : null),
            "soporte" => $data["soporte"] ?? null,
            "descripcion" => $data["descripcion"] ?? null,
            "estado" => $data["estado"] ?? null,
            "escritura" => $data["escritura"] ?? null,
            "matricula" => $data["matricula"] ?? null,
            "valor_compra" => limpiarNumero($data["valor_compra"] ?? null),
            "salvamenta" => $data["salvamenta"] ?? null,
            "depreciacion" => limpiarNumero($data["depreciacion"] ?? null),
            "depreciacion_niif" => limpiarNumero($data["depreciacion_niif"] ?? null),
            "meses" => $data["meses"] ?? null,
            "meses_niif" => $data["meses_niif"] ?? null,
            "tipo_adquisicion" => $data["tipo_adquisicion"] ?? null,
            "calibrado" => (!empty($data["calibrado"]) ? $data["calibrado"] : null),
            "observaciones" => $data["observaciones"] ?? null
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No se insertó ninguna fila en la base de datos.");
        }

        $idInsertado = $pdo->lastInsertId();
        $fechaColombia = date('Y-m-d H:i:s');


        // REGISTRAR ACTIVIDAD DE CREACIÓN
        registrarActividad(
            $pdo,
            $data['creado_por'],
            "Creó el inventario con código {$data['codigo']}",
            "inventario",
            $data['id']
        );
    }

    // Traer datos actualizados/insertados
    $id = !empty($data['id']) ? $data['id'] : $idInsertado;
    $stmt2 = $pdo->prepare("SELECT * FROM inventario WHERE id = :id");
    $stmt2->execute(["id" => $id]);
    $registro = $stmt2->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "msg" => !empty($data['id']) ? "Inventario actualizado con éxito" : "Inventario registrado con éxito",
        "data" => $registro,
        "id" => $id,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar el inventario: " . $e->getMessage()]);
}
