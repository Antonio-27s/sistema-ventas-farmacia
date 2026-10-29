<?php
/**
 * ============================================================
 * CONTROLADOR: Usuario
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Gestion del ciclo de vida de la sesion de autenticacion:
 *   - iniciar la sesion activa con cookies endurecidas y
 *     generar el token anti-CSRF de la sesion
 *   - verificar si existe una sesion autenticada
 *   - "recordar sesion" por 14 dias (cookie persistente)
 *   - timeout de inactividad (10 min, tipico POS)
 *   - mi perfil (accion "Ver perfil" del header)
 *   - la sesion en el panel temporal del shell ("Ver sesion";
 *     la accion sesion es el fallback sin JavaScript)
 *   - cerrar la sesion (logout): destruccion de sesion y
 *     borrado de la cookie
 */

declare(strict_types=1);

class UsuarioController
{
    /** Nombre de la cookie de sesion del sistema. */
    private const NOMBRE_SESION = 'SV_FARMACIA_SES';

    /** Duracion del "recordar sesion" (dias). */
    public const DIAS_REMEMBER = 14;

    /**
     * Timeout de inactividad (minutos): una terminal desatendida
     * no puede mantener la sesion abierta indefinidamente.
     * Para probarlo rapidamente, baje el valor a 1.
     */
    public const MINUTOS_INACTIVIDAD = 10;

    /**
     * Inicia (o reutiliza) la sesion activa con cookies
     * endurecidas. Debe ejecutarse ANTES de emitir cualquier
     * header() o salida, y una sola vez por peticion.
     */
    public function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(self::NOMBRE_SESION);

        // Parametros de la cookie de sesion:
        //  - lifetime 0 : cookie de sesion (muere al cerrar el navegador)
        //  - httponly   : inaccesible desde JavaScript (anti-XSS)
        //  - samesite   : Lax (mitiga CSRF)
        //  - secure     : false en desarrollo (HTTP local);
        //                 poner TRUE en produccion (HTTPS)
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        // Token anti-CSRF de la sesion (lo usan los formularios)
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Token anti-CSRF de la sesion actual (creado al iniciarla).
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf'];
    }

    /**
     * Indica si existe una sesion autenticada.
     */
    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario']['id_usuario']);
    }

    /**
     * Marca la sesion como "recordada" por N dias y reemite la
     * cookie con esa expiracion (pasa a ser persistente:
     * sobrevive al cierre del navegador).
     *
     * La expiracion TAMBIEN se guarda en la propia sesion
     * (recordado_hasta) y el front controller la hace cumplir
     * en cada peticion, aunque el navegador siga presentando
     * la cookie vigente.
     */
    public function recordarSesion(int $dias = self::DIAS_REMEMBER): void
    {
        $fin = time() + ($dias * 86400);

        $_SESSION['usuario']['recordado_hasta'] = $fin;

        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => $fin,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    /**
     * Fin de la ventana de sesion recordada
     * (0 = sesion normal, solo mientras el navegador este abierto).
     */
    public static function recordadoHasta(): int
    {
        return (int) ($_SESSION['usuario']['recordado_hasta'] ?? 0);
    }

    /**
     * "Ver sesion": renderiza el shell (dashboard) con el panel
     * de sesion YA ABIERTO. Es el fallback sin JavaScript del
     * boton "Ver sesion" del header (que solo alterna el panel).
     */
    public function mostrarSesion(): void
    {
        if (!self::estaAutenticado()) {
            // Sin sesion autenticada: se redirige al login
            header('Location: index.php?controlador=Auth&accion=mostrar');
            exit;
        }

        // Los datos de la sesion se reconstruyen desde la BD.
        // Si el usuario ya no existe (o fue desactivado), la
        // sesion queda huérfana: se cierra y se vuelve al login.
        $modelo  = new Usuario();
        $usuario = $modelo->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);

        if ($usuario === null || (int) $usuario['activo'] !== 1) {
            $this->cerrarSesion();
            return; // (cerrarSesion redirige y termina)
        }

        extract(contextoVista('Inicio', 'Sesión activa', true));

        require BASE_PATH . '/views/inicio/inicio.php';
    }

    /**
     * "Ver perfil": datos del usuario logueado (desde la BD)
     * + ultimo acceso exitoso (auditoria).
     */
    public function mostrarPerfil(): void
    {
        if (!self::estaAutenticado()) {
            header('Location: index.php?controlador=Auth&accion=mostrar');
            exit;
        }

        $modelo  = new Usuario();
        $usuario = $modelo->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);

        if ($usuario === null || (int) $usuario['activo'] !== 1) {
            $this->cerrarSesion();
            return;
        }

        $ultimos = (new IntentoLogin())->ultimosExitos();

        extract(contextoVista('Usuario', 'Mi perfil'));

        require BASE_PATH . '/views/usuario/perfil.php';
    }

    /**
     * Cierra la sesion activa (logout). Orden importante:
     *   1. borrar todos los datos de la sesion ($_SESSION)
     *   2. invalidar la cookie en el navegador: setcookie con
     *      el mismo nombre y fecha de expiracion en el pasado
     *      (los mismos path/domain/flags con los que se creo)
     *   3. destruir la sesion en el servidor (session_destroy
     *      elimina el archivo de sesion; NO borra la cookie,
     *      por eso el paso 2)
     * Finalmente redirige al formulario de login (con un
     * aviso si el cierre fue por inactividad).
     */
    public function cerrarSesion(bool $porInactividad = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // 1) Borrar los datos de la sesion
            $_SESSION = [];

            // 2) Invalidar la cookie en el navegador
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000, // ya expirada
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);

            // 3) Destruir la sesion en el servidor
            session_destroy();
        }

        $url = 'index.php?controlador=Auth&accion=mostrar';
        if ($porInactividad) {
            $url .= '&expiro=1';
        }

        header('Location: ' . $url);
        exit;
    }
}
