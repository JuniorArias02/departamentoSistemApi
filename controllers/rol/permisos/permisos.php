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
        'VER_PROPIOS' => 'ver_propios_mantenimientos',
        'CONTAR_TODOS_PENDIENTES' => 'contar_todos_pendientes_mantenimientos',
        'CONTAR_PROPIOS_PENDIENTES' => 'contar_propios_pendientes_mantenimientos',
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
    ],


    'ADMINISTRADOR_WEB' => [
        'CREAR_AVISO_ACTUALIZACION' => 'crear_aviso_actualizacion_web',
        'EDITAR_AVISO_ACTUALIZACION' => 'editar_aviso_actualizacion_web',
        'ELIMINAR_AVISO_ACTUALIZACION' => 'eliminar_aviso_actualizacion_web',
        'VER_AVISOS' => 'ver_avisos_actualizacion_web',
    ],
];
