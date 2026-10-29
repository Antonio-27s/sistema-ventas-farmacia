<?php
/**
 * ============================================================
 * DIAGNOSTICO DE DESPLIEGUE (solo desarrollo)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Abre: http://localhost/sistema_ventas/public/diagnostico.php
 *
 * Responde de un vistazo a las dudas de despliegue:
 *   - ¿Que copia del codigo esta sirviendo el servidor?
 *   - ¿Los archivos desplegados son los actuales?
 *   - ¿La BD esta conectada y con las migraciones al dia?
 *   - ¿El entorno PHP soporta lo que el sistema necesita?
 *
 * SEGURIDAD: solo funciona con APP_DEBUG=true. Al pasar a
 * produccion (APP_DEBUG=false) deja de responder; lo ideal
 * es eliminarlo.
 */

declare(strict_types=1);

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(403);
    exit('Diagnostico deshabilitado (APP_DEBUG=false).');
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';

$checks = [];

function agregar(
    array &$checks,
    string $nombre,
    bool $ok,
    string $detalle = ''
): void {
    $checks[] = ['nombre' => $nombre, 'ok' => $ok, 'detalle' => $detalle];
}

// ------------------------------------------------------------
// 1) ¿Que copia esta sirviendo el servidor?
// ------------------------------------------------------------
$real = realpath(__DIR__ . '/index.php');
agregar(
    $checks,
    'Copia que esta sirviendo el servidor',
    true,
    ($real !== false ? $real : '(no se pudo determinar)')
    . ' — ¿es la carpeta donde copias los archivos?'
);

// ------------------------------------------------------------
// 2) Version del codigo desplegado
// ------------------------------------------------------------
$versionOk = defined('APP_VERSION')
    && version_compare(APP_VERSION, '1.5.0', '>=');
agregar(
    $checks,
    'Version del codigo desplegado',
    $versionOk,
    defined('APP_VERSION')
        ? 'APP_VERSION = ' . APP_VERSION
        : 'No existe APP_VERSION → copia anterior a v1.5.0 (re-copiar)'
);

// ------------------------------------------------------------
// 3) Marcas en los archivos clave (detectan copias antiguas)
// ------------------------------------------------------------
function marcaArchivo(string $ruta, string $marca): string
{
    if (!is_file($ruta)) {
        return 'ARCHIVO NO EXISTE';
    }
    $contenido = (string) file_get_contents($ruta);
    return strpos($contenido, $marca) !== false
        ? 'ok'
        : 'SIN la marca esperada → archivo antiguo (re-copiar)';
}

$rutaLogin = BASE_PATH . '/views/auth/login.php';
agregar(
    $checks,
    'views/auth/login.php tiene el recuadro "recordar"',
    is_file($rutaLogin) && strpos((string) file_get_contents($rutaLogin), 'name="recordar"') !== false,
    marcaArchivo($rutaLogin, 'name="recordar"')
);

$rutaIndex = BASE_PATH . '/public/index.php';
agregar(
    $checks,
    'public/index.php tiene APP_DEBUG (debug de errores)',
    is_file($rutaIndex) && strpos((string) file_get_contents($rutaIndex), 'APP_DEBUG') !== false,
    marcaArchivo($rutaIndex, 'APP_DEBUG')
);

$rutaEsquema = BASE_PATH . '/config/esquema.php';
agregar(
    $checks,
    'config/esquema.php existe (autodiagnostico)',
    is_file($rutaEsquema),
    is_file($rutaEsquema) ? 'ok' : 'ARCHIVO NO EXISTE → copia antigua'
);

$rutaIntento = BASE_PATH . '/models/IntentoLogin.php';
agregar(
    $checks,
    'models/IntentoLogin.php existe (fuerza bruta/auditoria)',
    is_file($rutaIntento),
    is_file($rutaIntento) ? 'ok' : 'ARCHIVO NO EXISTE → copia antigua'
);

// ------------------------------------------------------------
// 4) Entorno PHP
// ------------------------------------------------------------
agregar(
    $checks,
    'PHP 8.0 o superior',
    PHP_VERSION_ID >= 80000,
    'PHP ' . PHP_VERSION
);
$argon2 = defined('PASSWORD_ARGON2ID');
agregar(
    $checks,
    'Extension argon2 (hash de contraseas)',
    $argon2,
    $argon2
        ? 'PASSWORD_ARGON2ID = ' . var_export(PASSWORD_ARGON2ID, true)
        : 'No disponible → los hashes quedaran en bcrypt (funciona)'
);
agregar(
    $checks,
    'Extension mbstring',
    function_exists('mb_substr'),
    function_exists('mb_substr') ? 'ok' : 'FALTA (XAMPP/WAMP la traen activada)'
);
agregar(
    $checks,
    'Extension pdo_mysql',
    extension_loaded('pdo_mysql'),
    extension_loaded('pdo_mysql') ? 'ok' : 'FALTA: sin ella no hay conexion'
);

// ------------------------------------------------------------
// 5) Base de datos: conexion, migraciones y datos
// ------------------------------------------------------------
try {
    $pdo = db();
    agregar(
        $checks,
        'Conexion a la base de datos',
        true,
        DB_HOST . ':' . DB_PORT . ' / BD "' . DB_NAME . '"'
    );

    $okTabla = true;
    try {
        $pdo->query('SELECT id_intento FROM intentos_login LIMIT 1');
    } catch (Throwable $e) {
        $okTabla = false;
    }
    agregar(
        $checks,
        'Tabla intentos_login (migracion)',
        $okTabla,
        $okTabla
            ? 'ok'
            : 'FALTA: ejecutar database/migracion_intentos_login.sql'
    );

    $okDni = true;
    try {
        $pdo->query('SELECT dni FROM usuario LIMIT 1');
    } catch (Throwable $e) {
        $okDni = false;
    }
    agregar(
        $checks,
        'Columna usuario.dni (migracion)',
        $okDni,
        $okDni
            ? 'ok'
            : 'FALTA: ejecutar database/alter_usuario_add_dni.sql'
    );

    $totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    agregar(
        $checks,
        'Usuarios en la BD (demo: 3)',
        $totalUsuarios > 0,
        $totalUsuarios . ' usuario(s)'
        . ($totalUsuarios < 3
            ? ' — ejecutar database/crear_usuario_demo.sql'
            : ' (admin / vdemo / ademo)')
    );
} catch (Throwable $e) {
    agregar(
        $checks,
        'Conexion a la base de datos',
        false,
        $e->getMessage()
    );
}

// ------------------------------------------------------------
// Vista
// ------------------------------------------------------------
$fallos = 0;
foreach ($checks as $c) {
    if (!$c['ok']) {
        $fallos++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico — Sistema de Ventas</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 30px;
            color: #223;
        }
        .contenedor {
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
            padding: 28px 32px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: #1a3c5e;
        }
        .sub {
            font-size: 13px;
            color: #667;
            margin: 0 0 18px;
        }
        .resumen {
            font-size: 15px;
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
        }
        .resumen.ok { background: #e6f4ea; color: #1e8e3e; }
        .resumen.ko { background: #fdecea; color: #b71c1c; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td {
            padding: 9px 10px;
            border-bottom: 1px solid #eef1f5;
            vertical-align: top;
        }
        td.estado { width: 50px; text-align: center; font-size: 16px; }
        td.nombre { width: 34%; font-weight: 600; }
        td.detalle { color: #445; word-break: break-all; font-size: 13px; }
        .nota {
            margin-top: 18px;
            font-size: 13px;
            color: #667;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Diagnóstico de despliegue — Sistema de Ventas Farmacia</h1>
        <p class="sub">
            Version de esta pagina: v1.5.0 · Solo funciona con APP_DEBUG=true
        </p>

        <div class="resumen <?= $fallos === 0 ? 'ok' : 'ko' ?>">
            <?= $fallos === 0
                ? '✔ Todo en orden: copia actual, BD al dia y entorno PHP correcto.'
                : '⚠ ' . $fallos . ' chequeo(s) con problemas — revisar los detalles.' ?>
        </div>

        <table>
            <?php foreach ($checks as $c): ?>
            <tr>
                <td class="estado"><?= $c['ok'] ? '✅' : '❌' ?></td>
                <td class="nombre"><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="detalle"><?= htmlspecialchars($c['detalle'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="nota">
            <strong>Como interpretar:</strong> si "Version del codigo
            desplegado" o las marcas de archivos marcan ❌, el servidor esta
            corriendo una copia antigua: <strong>eliminar por completo</strong>
            la carpeta <code>sistema_ventas</code> de tu <code>www/</code>,
            copiar de nuevo la carpeta del workspace, reiniciar Apache y
            refrescar con Ctrl+F5.
        </div>
    </div>
</body>
</html>
