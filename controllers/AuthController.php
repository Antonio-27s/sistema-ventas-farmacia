<?php
/**
 * ============================================================
 * CONTROLADOR: Autenticacion (Auth)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Muestra el formulario de login y procesa el envio.
 *
 * Protecciones aplicadas, en orden:
 *   1. Token anti-CSRF (hash_equals)
 *   2. Validacion de forma: usuario, DNI (8 digitos), tipo
 *   3. Bloqueo por fuerza bruta: 5 fallos/15 min por usuario
 *      o 20 fallos/15 min por IP (tabla intentos_login)
 *   4. Validacion contra BD: usuario + dni + rol + contrasena
 *      (Modelo Usuario: tiempo de respuesta constante y
 *       rehash transparente a Argon2id)
 *   5. Auditoria: cada intento (exito o fallo) se registra
 *      en intentos_login
 *   6. Anti session-fixation: session_regenerate_id
 *   7. Rotacion del token CSRF sobre la nueva sesion
 *   8. "Recordar sesion" 14 dias (opcional, recuadro)
 *
 * Tras el login, cada rol aterriza en SU modulo de inicio
 * (regla RBAC, matriz en el modelo Rol).
 */

declare(strict_types=1);

class AuthController
{
    /** Tipos de usuario que pueden entrar por este login. */
    private const TIPOS_VALIDOS = ['Vendedor', 'Administrador', 'Almacenero'];

    /** Umbrales anti fuerza bruta (ventana de 15 minutos). */
    private const VENTANA_INTENTOS   = 900; // segundos
    private const MAX_FALLOS_USUARIO = 5;
    private const MAX_FALLOS_IP      = 20;

    /**
     * Accion: mostrar el formulario de login.
     * Si ya hay sesion autenticada, se redirige al modulo de
     * inicio del rol (regla RBAC).
     */
    public function mostrar(): void
    {
        if (UsuarioController::estaAutenticado()) {
            $rolNombre = (new Rol())->nombrePorId(
                (int) $_SESSION['usuario']['id_rol']
            );

            header('Location: index.php?controlador=' . Rol::inicio((string) $rolNombre));
            exit;
        }

        $error  = null;
        // Aviso de "sesion termino por inactividad" (viene por URL)
        $expiro = isset($_GET['expiro']);
        require BASE_PATH . '/views/auth/login.php';
    }

    /**
     * Accion: procesar el login (solo via POST).
     */
    public function procesar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controlador=Auth&accion=mostrar');
            exit;
        }

        $ip       = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $intentos = new IntentoLogin();

        $error  = null;
        $expiro = false;

        // 1) Token anti-CSRF: el formulario debe llevar el token
        //    de ESTA sesion. Si no coincide, no se toca la BD.
        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            $error = 'El formulario expiró o es inválido. Vuelva a intentar.';
            require BASE_PATH . '/views/auth/login.php';
            return;
        }

        $usuario  = trim((string) ($_POST['usuario'] ?? ''));
        $dni      = trim((string) ($_POST['dni'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $tipo     = (string) ($_POST['tipo_usuario'] ?? '');

        // 2) Validacion de forma: los datos deben existir y
        //    tener el formato esperado antes de tocar la BD.
        if ($usuario === '' || $dni === '' || $password === '' || $tipo === '') {
            $error = 'Complete todos los campos.';
        } elseif (!preg_match('/^\d{8}$/', $dni)) {
            $error = 'El DNI debe contener exactamente 8 dígitos.';
        } elseif (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $error = 'Seleccione un tipo de usuario válido.';
        } else {
            // 3) Bloqueo por fuerza bruta (usuario o IP).
            //    Un intento BLOQUEADO no se registra: no se
            //    puso a prueba ninguna contrasena.
            if ($intentos->fallosRecientesUsuario($usuario, self::VENTANA_INTENTOS)
                    >= self::MAX_FALLOS_USUARIO
                || $intentos->fallosRecientesIp($ip, self::VENTANA_INTENTOS)
                    >= self::MAX_FALLOS_IP) {
                $error = 'Demasiados intentos fallidos. Inténtelo de nuevo en unos minutos.';
            } else {
                // 4) Validacion contra la BD (usuario + dni + rol
                //    + contrasena) en una sola consulta.
                $fila = (new Usuario())->autenticar($usuario, $dni, $password, $tipo);

                if ($fila === null) {
                    // 5) Auditoria: fallo registrado
                    $intentos->registrar($usuario, $ip, false);

                    // Mensaje generico: no revelar cual dato fue
                    // el incorrecto (usuario, dni, tipo o clave)
                    $error = 'Usuario, DNI, tipo o contraseña incorrectos.';
                } else {
                    // 5) Auditoria: exito registrado
                    $intentos->registrar($usuario, $ip, true);

                    // ---- Autenticacion exitosa ----------------

                    // 6) Regenerar el ID de sesion: mitiga el
                    //    ataque de session fixation.
                    session_regenerate_id(true);

                    // Fijar los datos de la sesion activa.
                    $_SESSION['usuario'] = [
                        'id_usuario'       => (int) $fila['id_usuario'],
                        'dni'              => $fila['dni'],
                        'nombre'           => $fila['nombre'],
                        'apellidos'        => trim(
                            $fila['apellido_paterno'] . ' ' . $fila['apellido_materno']
                        ),
                        'usuario'          => $fila['usuario'],
                        'id_rol'           => (int) $fila['id_rol'],
                        'ultima_actividad' => time(),
                    ];

                    // 7) Rotar el token CSRF: el token del
                    //    pre-login no vale para la sesion nueva.
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));

                    // 8) Recuadro "Recordar mi sesion por 14 dias".
                    if (isset($_POST['recordar'])) {
                        (new UsuarioController())->recordarSesion();
                    }

                    // Cada rol aterriza en su modulo de inicio
                    // (matriz INICIO_POR_ROL del modelo Rol).
                    $rolNombre = (new Rol())->nombrePorId((int) $fila['id_rol']);
                    header(
                        'Location: index.php?controlador='
                        . Rol::inicio((string) $rolNombre)
                    );
                    exit;
                }
            }
        }

        // Intento rechazado: volver a la vista de login
        // mostrando el error (con los campos re-llenados).
        require BASE_PATH . '/views/auth/login.php';
    }
}
