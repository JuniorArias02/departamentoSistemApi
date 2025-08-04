CREATE TABLE `pc_equipos` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_equipo` varchar(255),
  `marca` varchar(255),
  `modelo` varchar(255),
  `serial` varchar(255),
  `tipo` varchar(255),
  `propiedad` enum('empleado','empresa'),
  `ip_fija` varchar(255),
  `numero_inventario` varchar(255),
  `sede_id` int,
  `area_id` int,
  `responsable_id` int,
  `estado` varchar(255),
  `imagen_url` text,
  `fecha_entrega` date,
  `descripcion_general` text,
  `garantia_meses` int,
  `forma_adquisicion` enum('compra','alquiler','donacion','comodato'),
  `observaciones` text
);

CREATE TABLE `pc_caracteristicas_tecnicas` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `procesador` varchar(255),
  `memoria_ram` varchar(255),
  `disco_duro` varchar(255),
  `tarjeta_video` varchar(255),
  `tarjeta_red` varchar(255),
  `tarjeta_sonido` varchar(255),
  `usb` tinyint,
  `unidad_cd` tinyint,
  `parlantes` tinyint,
  `drive` tinyint,
  `monitor` varchar(255),
  `teclado` varchar(255),
  `mouse` varchar(255),
  `internet` varchar(255),
  `velocidad_red` varchar(255),
  `capacidad_disco` varchar(255)
);

CREATE TABLE `pc_mantenimientos` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `tipo_mantenimiento` enum('preventivo','correctivo'),
  `descripcion` text,
  `fecha` date,
  `empresa_responsable_id` int,
  `repuesto` boolean,
  `cantidad_repuesto` int,
  `costo_repuesto` decimal,
  `nombre_repuesto` varchar(255),
  `responsable_mantenimiento` varchar(255),
  `firma_personal_cargo` text,
  `firma_sistemas` text
);

CREATE TABLE `pc_cronograma_mantenimientos` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `fecha_programada` date,
  `fecha_ejecucion` date,
  `estado_cumplimiento` enum('pendiente','no_aplica','realizado'),
  `fecha_ultimo_mantenimiento` date
);

CREATE TABLE `pc_entregas` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `funcionario_id` int,
  `fecha_entrega` date,
  `firma_entrega` text,
  `firma_recibe` text,
  `devuelto` boolean
);

CREATE TABLE `pc_perifericos_entregados` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `entrega_id` int,
  `nombre` varchar(255),
  `cantidad` int,
  `marca` varchar(255),
  `modelo` varchar(255),
  `observaciones` text
);

CREATE TABLE `pc_licencias_software` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `windows` tinyint,
  `office` tinyint,
  `nitro` tinyint
);

CREATE TABLE `pc_historial_asignaciones` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `equipo_id` int,
  `personal_id` int,
  `fecha_asignacion` date,
  `fecha_devolucion` date,
  `observaciones` text
);

CREATE TABLE `personal` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(255),
  `cedula` varchar(255),
  `telefono` varchar(255),
  `cargo` varchar(255),
  `proceso` varchar(255)
);

CREATE TABLE `areas` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(255),
  `sede_id` int
);

CREATE TABLE `datos_empresa` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(255),
  `nit` varchar(255),
  `direccion` varchar(255),
  `telefono` varchar(255),
  `email` varchar(255),
  `representante_legal` varchar(255),
  `ciudad` varchar(255)
);

ALTER TABLE `pc_equipos` ADD FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

ALTER TABLE `pc_equipos` ADD FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

ALTER TABLE `areas` ADD FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

ALTER TABLE `pc_equipos` ADD FOREIGN KEY (`responsable_id`) REFERENCES `personal` (`id`);

ALTER TABLE `pc_caracteristicas_tecnicas` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_mantenimientos` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_mantenimientos` ADD FOREIGN KEY (`empresa_responsable_id`) REFERENCES `datos_empresa` (`id`);

ALTER TABLE `pc_cronograma_mantenimientos` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_entregas` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_entregas` ADD FOREIGN KEY (`funcionario_id`) REFERENCES `personal` (`id`);

ALTER TABLE `pc_perifericos_entregados` ADD FOREIGN KEY (`entrega_id`) REFERENCES `pc_entregas` (`id`);

ALTER TABLE `pc_licencias_software` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_historial_asignaciones` ADD FOREIGN KEY (`equipo_id`) REFERENCES `pc_equipos` (`id`);

ALTER TABLE `pc_historial_asignaciones` ADD FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`);
