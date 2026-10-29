<?php
/**
 * ============================================================
 * CONTROLADOR: Usuarios (gestion de cuentas)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Solo el rol Administrador entra a este modulo (matriz RBAC;
 * el router lo verifica ANTES de despachar).
 *
 * Acciones (whitelist en el router):
 *   - listar          : listado de usuarios (default)
 *   - nuevo           : formulario de alta
 *   - editar          : formulario de edicion (&id)
 *   - guardar         : crea o actualiza (POST + CSRF)
 *   - toggle_estado   : activar/desactivar (POST + CSRF)
 *   - password        : formulario para definir nueva contrasena
 *   - guardar_password: guarda la contrasena (POST + CSRF)
 *
 * Reglas de negocio:
 *   - La contrasena la define el Administrador DIRECTAMENTE
 *     (sin temporales ni auto-servicio por parte del usuario).
 *     Al CREAR una cuenta se fija su contrasena inicial; luego
 *     se cambia solo por la accion "password" (el form de
 *     edicion NO toca la contrasena).
 *   - Login y DNI son unicos (un login por persona).
 *   - Nadie puede desactivar su propia cuenta ni cambiar su
 *     propio rol (anti lockout).
 *   - Todo cambio es POST + token CSRF + redirigir despues del
 *     POST (PRG, evita re-enviar con el back).
 */

declare(strict_types=1);

class UsuariosController
{
    /** Longitud minima de la contrasena (nueva e inicial). */
    private const MIN_LONGITUD = 8;

    /**
     * Accion: listado de usuarios (default).
     */
    public function listar(): void
    {
        $modelo   = new Usuario();
        $intentos = new IntentoLogin();

        $usuarios = $modelo->listarConRol();
        $ultimos  = $intentos->ultimosExitos();
        $idActual = (int) $_SESSION['usuario']['id_usuario'];
        $msg      = (string) ($_GET['msg'] ?? '');

        extract(contextoVista('Usuarios', 'Gestión de usuarios'));
        require BASE_PATH . '/views/usuarios/usuarios.php';
    }

    /**
     * Accion: formulario de usuario (nuevo, o edicion con &id).
     */
    public function formUsuario(): void
    {
        // $destino (convension de usuarios_password.php): el
        // usuario objetivo del form. NUNCA $usuario: la shell
        // (contextoVista) define $usuario = la cuenta logueada
        // y extract() lo pisaria.
        $id        = (int) ($_GET['id'] ?? 0);
        $esEdicion = $id > 0;
        $destino   = $esEdicion ? (new Usuario())->obtenerConRol($id) : null;

        if ($esEdicion && $destino === null) {
            header('Location: index.php?controlador=Usuarios&msg=error');
            exit;
        }

        $esYo = $esEdicion
            && (int) $destino['id_usuario'] === (int) $_SESSION['usuario']['id_usuario'];

        $errores = [];
        $campos  = $this->camposUsuario((array) ($destino ?? []));
        if ($destino === null) {
            $campos['activo'] = 1; // alta nueva: activa por defecto
        }
        $roles   = (new Rol())->listar();

        extract(contextoVista('Usuarios', $esEdicion ? 'Editar usuario' : 'Nuevo usuario'));
        require BASE_PATH . '/views/usuarios/usuario_form.php';
    }

    /**
     * Accion: crear o actualizar usuario (POST + CSRF + validacion).
     */
    public function guardarUsuario(): void
    {
        $url = 'index.php?controlador=Usuarios&accion=mostrar';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $url);
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: ' . $url . '&msg=error');
            exit;
        }

        $id      = (int) ($_POST['id_usuario'] ?? 0);
        $errores = $this->validarUsuario($_POST, $id);

        if ($errores === []) {
            $modelo = new Usuario();
            $d      = $this->camposUsuario($_POST);

            if ($id > 0) {
                $modelo->actualizar($id, $d);
            } else {
                $d['password'] = Usuario::crearHash((string) ($_POST['password'] ?? ''));
                $modelo->crear($d);
            }

            header('Location: ' . $url . '&msg=usuario-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form con lo enviado
        // ($destino, nunca $usuario: ver formUsuario)
        $destino   = $id > 0 ? (new Usuario())->obtenerConRol($id) : null;
        $esEdicion = $id > 0 && $destino !== null;
        $esYo      = $esEdicion
            && (int) $destino['id_usuario'] === (int) $_SESSION['usuario']['id_usuario'];
        $campos    = $this->camposUsuario($_POST);
        $roles     = (new Rol())->listar();

        extract(contextoVista('Usuarios', $esEdicion ? 'Editar usuario' : 'Nuevo usuario'));
        require BASE_PATH . '/views/usuarios/usuario_form.php';
    }

    /**
     * Accion: activar/desactivar una cuenta (POST + CSRF).
     * No permite desactivar la cuenta propia.
     */
    public function toggleEstado(): void
    {
        $url = 'index.php?controlador=Usuarios&accion=mostrar';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $url);
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: ' . $url . '&msg=error');
            exit;
        }

        $id = (int) ($_POST['usuario_id'] ?? 0);

        // Invalido o intento de desactivarse a si mismo
        if ($id <= 0 || $id === (int) $_SESSION['usuario']['id_usuario']) {
            header('Location: ' . $url . '&msg=error');
            exit;
        }

        $modelo  = new Usuario();
        $destino = $modelo->obtenerPorId($id);

        if ($destino === null) {
            header('Location: ' . $url . '&msg=error');
            exit;
        }

        $modelo->cambiarEstado($id, (int) $destino['activo'] !== 1);

        header('Location: ' . $url . '&msg=estado-ok');
        exit;
    }

    /**
     * Accion: formulario para definir la contrasena de un usuario.
     */
    public function formPassword(): void
    {
        $url     = 'index.php?controlador=Usuarios&accion=mostrar';
        $id      = (int) ($_GET['usuario_id'] ?? 0);
        $destino = (new Usuario())->obtenerConRol($id);

        if ($destino === null) {
            header('Location: ' . $url . '&msg=error');
            exit;
        }

        $error = null;

        extract(contextoVista('Usuarios', 'Definir contraseña'));
        require BASE_PATH . '/views/usuarios/usuarios_password.php';
    }

    /**
     * Accion: guardar la contrasena nueva (POST + CSRF + forma).
     */
    public function guardarPassword(): void
    {
        $urlBase = 'index.php?controlador=Usuarios&accion=mostrar';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $urlBase);
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        $id   = (int) ($_POST['usuario_id'] ?? 0);

        if (!hash_equals(UsuarioController::csrfToken(), $csrf) || $id <= 0) {
            header('Location: ' . $urlBase . '&msg=error');
            exit;
        }

        $pass    = (string) ($_POST['password'] ?? '');
        $conf    = (string) ($_POST['password2'] ?? '');
        $modelo  = new Usuario();
        $destino = $modelo->obtenerConRol($id);

        if ($destino === null) {
            header('Location: ' . $urlBase . '&msg=error');
            exit;
        }

        $error = null;
        if (mb_strlen($pass) < self::MIN_LONGITUD) {
            $error = 'La contraseña debe tener al menos '
                . self::MIN_LONGITUD . ' caracteres.';
        } elseif ($pass !== $conf) {
            $error = 'Las contraseñas no coinciden.';
        }

        if ($error === null) {
            $modelo->definirPassword($id, Usuario::crearHash($pass));
            header('Location: ' . $urlBase . '&msg=pass-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form (el usuario
        // destino sigue siendo el mismo: $destino)
        extract(contextoVista('Usuarios', 'Definir contraseña'));
        require BASE_PATH . '/views/usuarios/usuarios_password.php';
    }

    // ------------------------------------------------------------
    // Ayudas del controlador
    // ------------------------------------------------------------

    /**
     * Campos de perfil del form, normalizados desde el POST (o
     * desde la fila de la BD al re-llenar en edicion). La
     * contrasena NUNCA va en estos campos.
     *
     * @param array<string, mixed> $p
     * @return array<string, mixed>
     */
    private function camposUsuario(array $p): array
    {
        return [
            'usuario'          => trim((string) ($p['usuario'] ?? '')),
            'dni'              => (string) ($p['dni'] ?? ''),
            'nombre'           => trim((string) ($p['nombre'] ?? '')),
            'apellido_paterno' => trim((string) ($p['apellido_paterno'] ?? '')),
            'apellido_materno' => trim((string) ($p['apellido_materno'] ?? '')),
            'id_rol'           => (int) ($p['id_rol'] ?? 0),
            'activo'           => isset($p['activo']) ? 1 : 0,
        ];
    }

    /**
     * Valida el form de usuario (alta o edicion). Devuelve la
     * lista de errores (vacia = todo ok).
     *
     * @param array<string, mixed> $p
     * @return string[]
     */
    private function validarUsuario(array $p, int $id): array
    {
        if ($id > 0 && (new Usuario())->obtenerPorId($id) === null) {
            return ['El usuario a editar no existe.'];
        }

        $login  = trim((string) ($p['usuario'] ?? ''));
        $dni    = (string) ($p['dni'] ?? '');
        $nombre = trim((string) ($p['nombre'] ?? ''));
        $ap     = trim((string) ($p['apellido_paterno'] ?? ''));
        $am     = trim((string) ($p['apellido_materno'] ?? ''));
        $idRol  = (int) ($p['id_rol'] ?? 0);
        $activo = isset($p['activo']) ? 1 : 0;
        $esYo   = $id > 0 && $id === (int) $_SESSION['usuario']['id_usuario'];

        $errores = [];

        if ($login === '') {
            $errores[] = 'El usuario (login) es obligatorio.';
        } elseif (mb_strlen($login) > 50) {
            $errores[] = 'El usuario no puede superar 50 caracteres.';
        } elseif ((new Usuario())->existeLogin($login, $id)) {
            $errores[] = 'Ese usuario (login) ya existe.';
        }

        if (!preg_match('/^\d{8}$/', $dni)) {
            $errores[] = 'El DNI debe tener exactamente 8 dígitos.';
        } elseif ((new Usuario())->existeDni($dni, $id)) {
            $errores[] = 'Ya existe una cuenta con ese DNI.';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores[] = 'El nombre no puede superar 100 caracteres.';
        }

        if ($ap === '') {
            $errores[] = 'El apellido paterno es obligatorio.';
        } elseif (mb_strlen($ap) > 100) {
            $errores[] = 'El apellido paterno no puede superar 100 caracteres.';
        }

        if ($am === '') {
            $errores[] = 'El apellido materno es obligatorio.';
        } elseif (mb_strlen($am) > 100) {
            $errores[] = 'El apellido materno no puede superar 100 caracteres.';
        }

        if ((new Rol())->nombrePorId($idRol) === null) {
            $errores[] = 'Seleccione un rol válido.';
        } elseif ($esYo) {
            $miRol = (int) (new Usuario())->obtenerPorId($id)['id_rol'];
            if ($idRol !== $miRol) {
                $errores[] = 'No puedes cambiar el rol de tu propia cuenta.';
            }
        }

        if ($esYo && $activo === 0) {
            $errores[] = 'No puedes desactivar tu propia cuenta.';
        }

        // La contrasena inicial solo existe en el alta
        if ($id === 0) {
            $pass = (string) ($p['password'] ?? '');
            $conf = (string) ($p['password2'] ?? '');

            if (mb_strlen($pass) < self::MIN_LONGITUD) {
                $errores[] = 'La contraseña inicial debe tener al menos '
                    . self::MIN_LONGITUD . ' caracteres.';
            } elseif ($pass !== $conf) {
                $errores[] = 'Las contraseñas no coinciden.';
            }
        }

        return $errores;
    }
}
