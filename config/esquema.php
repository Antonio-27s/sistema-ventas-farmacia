<?php
/**
 * ============================================================
 * VERIFICACION DEL ESQUEMA
 * Proyecto: Sistema de Ventas - Farmacia (MVC PHP)
 * ============================================================
 * Antes de atender peticiones, verifica que la base de datos
 * tenga lo minimo que la aplicacion necesita. Si falta algo
 * (tipico: BD creada con una version previa y sin ejecutar
 * las migraciones), lanza un error con la instruccion EXACTA
 * para solucionarlo, en lugar de un "Error interno" generico.
 */

declare(strict_types=1);

function verificar_esquema(): void
{
    static $verificado = false;
    if ($verificado) {
        return;
    }

    $pdo = db();

    // 1) Tabla intentos_login (auditoria + fuerza bruta del login)
    try {
        $pdo->query('SELECT id_intento FROM intentos_login LIMIT 1');
    } catch (PDOException $e) {
        error_log('Esquema incompleto: falta intentos_login. ' . $e->getMessage());
        throw new RuntimeException(
            'Falta la tabla "intentos_login" en la base de datos. '
            . 'Ejecuta database/migracion_intentos_login.sql '
            . '(o re-importa database/farmacia_v2.sql completa).'
        );
    }

    // 2) Columna dni en usuario (la exige el login)
    try {
        $pdo->query('SELECT dni FROM usuario LIMIT 1');
    } catch (PDOException $e) {
        error_log('Esquema incompleto: falta usuario.dni. ' . $e->getMessage());
        throw new RuntimeException(
            'Falta la columna "dni" en la tabla "usuario". '
            . 'Ejecuta database/alter_usuario_add_dni.sql '
            . '(o re-importa database/farmacia_v2.sql completa).'
        );
    }

    $verificado = true;
}
