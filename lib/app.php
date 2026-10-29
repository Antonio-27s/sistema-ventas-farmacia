<?php
/**
 * ============================================================
 * HELPERS DE LA APLICACION
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Funciones pequenas que varios controladores repiten. Se
 * cargan una sola vez desde public/index.php, ANTES de
 * despachar ningun modulo.
 *
 * Centralizan dos convenciones:
 *   - contextoVista(): las 6 variables que el shell
 *     (views/partes/cabecera.php y pie.php) espera que
 *     defina cada controlador: $usuario, $rolNombre,
 *     $modulosAside, $moduloActual, $panelSesion, $titulo.
 *   - respuestaJson(): la respuesta JSON estandar de las
 *     acciones que consumen el JS de los modulos (&json=1).
 */

declare(strict_types=1);

/**
 * Contexto del shell para pintar una vista.
 *
 * Uso en el controlador (una sola linea en vez de seis):
 *     extract(contextoVista('Producto', 'Catálogo de productos'));
 *
 * @return array{
 *     usuario: array<string, mixed>|null,
 *     rolNombre: string|null,
 *     modulosAside: string[],
 *     moduloActual: string,
 *     panelSesion: bool,
 *     titulo: string
 * }
 */
function contextoVista(string $moduloActual, string $titulo, bool $panelSesion = false): array
{
    $usuario   = (new Usuario())->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);
    $rolNombre = (new Rol())->nombrePorId((int) $usuario['id_rol']);

    return [
        'usuario'      => $usuario,
        'rolNombre'    => $rolNombre,
        'modulosAside' => Rol::modulosParaAside((string) $rolNombre),
        'moduloActual' => $moduloActual,
        'panelSesion'  => $panelSesion,
        'titulo'       => $titulo,
    ];
}

/**
 * Responde con JSON y termina la peticion.
 *
 * Lo consumen las acciones &json=1 del JS de los modulos
 * (ver public/js/). Sin &json=1 la misma accion responde
 * HTML o redirige (funciona sin JS).
 *
 * @param array<string, mixed> $datos
 */
function respuestaJson(array $datos): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
