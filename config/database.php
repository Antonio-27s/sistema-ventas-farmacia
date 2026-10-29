<?php
/**
 * ============================================================
 * CONFIGURACION DE LA CONEXION A LA BASE DE DATOS
 * Proyecto: Sistema de Ventas - Farmacia (MVC PHP)
 * ============================================================
 * - APP_DEBUG: en true, los errores se muestran en pantalla
 *   (desarrollo). En produccion debe ser false.
 * - Conexion unica (singleton) mediante PDO.
 * - Errores en modo excepcion (PDO::ERRMODE_EXCEPTION).
 * - Emulacion de preparados DESACTIVADA: usa los prepared
 *   statements nativos de MySQL (proteccion real ante SQL
 *   injection). TODAS las consultas del sistema deben usar
 *   prepared statements a traves de esta conexion.
 */

declare(strict_types=1);

// ------------------------------------------------------------
// VERSION DE LA APLICACION
// Aparece en el pie de las vistas: sirve para confirmar de un
// vistazo que el servidor esta corriendo la ultima copia del
// codigo (al copiar archivos a www/sistema_ventas, etc.).
// ------------------------------------------------------------
define('APP_VERSION', '1.10.0');

// ------------------------------------------------------------
// MODO DE DESARROLLO
// true  → los errores muestran su detalle en pantalla
//         (para diagnosticar rapido).
// false → solo mensaje generico; el detalle va al log.
// En produccion SIEMPRE false.
// ------------------------------------------------------------
define('APP_DEBUG', true);

// ------------------------------------------------------------
// CREDENCIALES Y PARAMETROS DE CONEXION
// Ajustar segun el entorno local (XAMPP: por defecto root y
// password vacio, puerto 3306).
// ------------------------------------------------------------
define('DB_HOST',    'localhost');
define('DB_PORT',    '3308');
define('DB_NAME',    'farmacia');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna la unica instancia PDO del sistema.
 * La conexion se abre la primera vez que se necesita (lazy).
 *
 * @return PDO
 * @throws RuntimeException si no se puede conectar.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // El detalle tecnico queda en el log, no en el navegador
            error_log('Error de conexion BD: ' . $e->getMessage());
            throw new RuntimeException(
                'No se pudo conectar con la base de datos. '
                . 'Verifique que MySQL/MariaDB este activo en '
                . DB_HOST . ':' . DB_PORT
                . ' y que la base "' . DB_NAME . '" exista.'
            );
        }
    }

    return $pdo;
}
