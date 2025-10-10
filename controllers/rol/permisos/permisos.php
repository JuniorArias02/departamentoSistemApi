<?php

const PERMISOS = [
    // USUARIOS
    'USUARIOS' => [
        'MENU_ITEM' => 'menu_item_usuario',
        'ACCESO_MODULO' => 'acceso_modulo_usuario',
        'CREAR' => 'crear_usuario',
        'EDITAR' => 'editar_usuario',
        'ELIMINAR' => 'eliminar_usuario',
        'ACTUALIZAR' => 'actualizar_usuario',
        'VER_DATOS' => 'ver_datos_usuarios',
    ],

    // SISTEMA
    'SISTEMA' => [
        'INGRESAR_DASHBOARDADMIN' => 'ingresar_dashboardAdmin',
        'INGRESAR_SIDEBAR_ADMIN' => 'ingresar_sidebarAdmin',
    ],

    // ROLES
    'ROLES' => [
        'MENU_ITEM' => 'menu_item_roles',
        'ACCESO_MODULO' => 'acceso_modulo_roles',
        'VER_LISTADO' => 'ver_listado_roles',
        'CREAR' => 'crear_roles',
        'EDITAR' => 'editar_roles',
        'ASIGNAR_PERMISOS' => 'asignar_permisos',
    ],

    // MANTENIMIENTOS
    'MANTENIMIENTOS' => [
        'VER_DATOS' => 'ver_datos_mantenimientos',
        'VER_FORMULARIO' => 'ver_formulario_mantenimiento',
        'MARCAR_REVISADO' => 'marcar_revisado_mantenimiento',
        'VER_DETALLE' => 'ver_detalle_mantenimiento',
        'CREAR' => 'crear_mantenimiento',
        'EDITAR' => 'editar_mantenimiento',
        'ELIMINAR' => 'eliminar_mantenimiento',
        'VER_TODOS' => 'ver_todos_mantenimientos',
        'VER_TODOS_AGENDADOS' => 'ver_todos_eventos_agendados',
        'VER_TODOS_EVENTOS_AGENDADOS' => 'ver_todos_eventos_agendados',
        'VER_PROPIOS' => 'ver_propios_mantenimientos',
        'CONTAR_TODOS_PENDIENTES' => 'contar_todos_pendientes_mantenimientos',
        'CONTAR_PROPIOS_PENDIENTES' => 'contar_propios_pendientes_mantenimientos',
        'RECIBIR_MANTENIMIENTO' => 'recibir_mantenimiento',
        'EXPORTAR_TODOS' => 'exportar_todos_informes_mantenimientos',
        'EXPORTAR_PROPIOS' => 'exportar_propios_informes_mantenimientos',
        'EXPORTAR_ASIGNADOS' => 'exportar_informes_asignados_mantenimientos',
        'GRAFICAR_MANTENIMIENTO_PROPIA_SEDE' => 'graficar_mantenimiento_propia_sede',

    ],
    'AGENDAMIENTO_MANTENIMIENTOS' => [
        'MENU_ITEM' => "menu_item_agendamiento_mantenimientos",
        'VER_CALENDARIO' => "ver_calendario_mantenimientos",
        'VER_PROGRAMADOS' => "ver_programados_mantenimientos",
        'VER_TODOS' => "ver_todos_eventos_agendados",
    ],

    // GESTIÓN DE PERMISOS
    'GESTION_PERMISOS' => [
        'MENU_ITEM' => 'menu_item_permisos',
        'CREAR' => 'crear_permiso',
        'ASIGNAR' => 'asignar_permisos',
    ],

    // INVENTARIO
    'INVENTARIO' => [
        'VER_DATOS' => 'ver_datos_inventario',
        'VER_FORMULARIO' => 'ver_formulario_inventario',
        'CREAR' => 'crear_inventario',
        'EDITAR' => 'editar_inventario',
        'ELIMINAR' => 'eliminar_inventario',
        'EXPORTAR' => 'exportar_inventario',
        'GRAFICAR_INVENTARIO_PROPIA_SEDE' => 'graficar_inventario_propia_sede',
    ],


    'ADMINISTRADOR_WEB' => [
        'CREAR_AVISO_ACTUALIZACION' => 'crear_aviso_actualizacion_web',
        'EDITAR_AVISO_ACTUALIZACION' => 'editar_aviso_actualizacion_web',
        'ELIMINAR_AVISO_ACTUALIZACION' => 'eliminar_aviso_actualizacion_web',
        'VER_AVISOS' => 'ver_avisos_actualizacion_web',
    ],

    // QUIPOS DE COMPUTO
    'GESTION_EQUIPOS' => [
        'ELIMINAR' => 'eliminar_equipo',
        'ELIMINAR_ACTA_MANTENIMIENTO' => 'eliminar_acta_mantenimiento'
    ],

    'GESTION_COMPRA_PEDIDOS' => [
        'RECIBIR_NUEVOS_PEDIDOS' => 'recibir_correo_nuevos_pedidos',
        'VER_PEDIDOS_PROPIOS' => 'ver_pedidos_propios',
        'VER_PEDIDOS_ENCARGADO' => 'ver_pedidos_encargado',
        'RECHAZAR_PEDIDO' => 'rechazar_pedido',
        'APROBAR_PEDIDO' => 'aprobar_pedido',
        'CREAR_PEDIDO' => 'crear_pedido',
        'CREAR_ENTREGA_SOLICITUD' => 'crear_entrega_solicitud',
        'CREAR_DESCUENTO_FIJOS' => 'crear_descuento_fijos',
        'MARCAR_ENTREGA' => 'marcar_entrega_item_pedido',
    ],

    'REPORTES' => [
        'RECIBIR_REPORTES' => 'recibir_reportes',
    ]

];