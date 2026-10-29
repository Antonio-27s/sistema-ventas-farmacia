<?php
/**
 * ============================================================
 * MODELO: Usuario
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `usuario` (y su rol, para el login).
 * Regla: TODA consulta usa prepared statements.
 *
 * Seguridad del login:
 *   - Hash: Argon2id (con respaldo a bcrypt si la compilacion
 *     de PHP no lo incluye).
 *   - Rehash transparente: al loguear, si el hash guardado esta
 *     en un algoritmo/costo anterior, se re-hasa solo (los
 *     usuarios demo creados con bcrypt migran sin accion manual).
 *   - Tiempo de respuesta constante: si la fila no existe, se
 *     ejecuta password_verify contra un hash dummy, para que no
 *     sea posible enumerar logins validos midiendo el tiempo.
 */

declare(strict_types=1);

class Usuario
{
    private PDO $pdo;

    /**
     * Hash precalculado de una cadena aleatoria. Solo se usa
     * para ejecutar password_verify (y su costo computacional)
     * cuando la fila del usuario no existe.
     */
    private const HASH_DUMMY =
        '$argon2id$v=19$m=65536,t=4,p=1$xmoC5XoJ272GrtvztQWmlA$T6VCMcsDujQdulWK8c9QjA0q+DUVGfqo2kETCpYN9Rk';

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Algoritmo de hash del sistema: Argon2id (PHC 2015+),
     * con respaldo a bcrypt.
     *
     * Devuelve string: desde PHP 7.4 las constantes PASSWORD_*
     * valen 'argon2id' / 'bcrypt' (string); solo en PHP 7.2-7.3
     * eran int. password_hash() las acepta en ambos tipos.
     */
    private static function algoritmo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /**
     * Valida los 4 datos del login contra la BD:
     *   usuario + dni + tipo de usuario (rol) + contrasena.
     *
     * - Solo usuarios activos (activo = 1).
     * - El "tipo de usuario" se valida con el nombre del rol
     *   mediante un JOIN usuario + rol (si el usuario es
     *   Administrador pero elige "Vendedor", el login falla).
     * - La contrasena se valida contra el hash con
     *   password_verify() (nunca se compara en claro).
     * - Si el hash guardado esta desactualizado, se re-hasa
     *   con el algoritmo actual (migracion transparente).
     * - El hash NUNCA sale del modelo: se quita antes de
     *   devolver la fila.
     *
     * @return array|null fila del usuario, o null si alguno
     *                    de los datos no es valido.
     */
    public function autenticar(
        string $usuario,
        string $dni,
        string $password,
        string $tipoUsuario
    ): ?array {
        $sql = 'SELECT u.*
               FROM usuario u
               INNER JOIN rol r ON r.id_rol = u.id_rol
               WHERE u.usuario = :usuario
                 AND u.dni     = :dni
                 AND r.nombre  = :tipo
                 AND u.activo  = 1
               LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':dni'     => $dni,
            ':tipo'    => $tipoUsuario,
        ]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            // Fila inexistente: igualar el tiempo de respuesta
            // con un verify dummy (anti enumeracion por timing).
            password_verify($password, self::HASH_DUMMY);
            return null;
        }

        if (!password_verify($password, $fila['password'])) {
            return null;
        }

        // Rehash transparente: si el hash guardado no usa el
        // algoritmo/costo actual, se actualiza con la clave
        // que acaba de validarse.
        if (password_needs_rehash($fila['password'], self::algoritmo())) {
            $nuevoHash = password_hash($password, self::algoritmo());

            $upd = $this->pdo->prepare(
                'UPDATE usuario SET password = :hash WHERE id_usuario = :id'
            );
            $upd->execute([
                ':hash' => $nuevoHash,
                ':id'   => (int) $fila['id_usuario'],
            ]);
        }

        unset($fila['password']);
        return $fila;
    }

    /**
     * Obtiene un usuario por su id (para reconstruir los datos
     * de la sesion activa desde la BD).
     *
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql  = 'SELECT * FROM usuario WHERE id_usuario = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            return null;
        }

        unset($fila['password']);
        return $fila;
    }

    /**
     * Obtiene un usuario por su id con el NOMBRE del rol
     * (para las vistas de gestion; obtenerPorId no trae el rol).
     *
     * @return array|null
     */
    public function obtenerConRol(int $id): ?array
    {
        $sql = 'SELECT u.*, r.nombre AS rol
               FROM usuario u
               INNER JOIN rol r ON r.id_rol = u.id_rol
               WHERE u.id_usuario = :id
               LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            return null;
        }

        unset($fila['password']);
        return $fila;
    }

    /**
     * Genera el hash con el algoritmo del sistema (fuente unica;
     * lo usa el controlador Usuarios al definir contraseñas).
     */
    public static function crearHash(string $password): string
    {
        return (string) password_hash($password, self::algoritmo());
    }

    /**
     * Lista a todos los usuarios con su rol (para la gestion).
     * Primero los activos, luego por nombre de login.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarConRol(): array
    {
        $sql = 'SELECT u.*, r.nombre AS rol
               FROM usuario u
               INNER JOIN rol r ON r.id_rol = u.id_rol
               ORDER BY u.activo DESC, u.usuario ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Activa/desactiva una cuenta (la usa el Administrador;
     * el controlador impide desactivarse a si mismo).
     */
    public function cambiarEstado(int $id, bool $activo): void
    {
        $sql = 'UPDATE usuario SET activo = :activo WHERE id_usuario = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':activo' => $activo ? 1 : 0,
            ':id'     => $id,
        ]);
    }

    /**
     * Define la contrasena del usuario (la define el
     * Administrador directamente; llega ya hasheada).
     */
    public function definirPassword(int $id, string $hashNuevo): void
    {
        $sql = 'UPDATE usuario SET password = :hash WHERE id_usuario = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':hash' => $hashNuevo,
            ':id'   => $id,
        ]);
    }

    /**
     * ¿Existe ya un login con ese nombre? (la unica para
     * la cuenta: uq_usuario_login). En edicion se excluye
     * el propio usuario del chequeo.
     */
    public function existeLogin(string $usuario, int $idExcluido = 0): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuario WHERE usuario = :login';
        $params = [':login' => $usuario];

        if ($idExcluido > 0) {
            $sql         .= ' AND id_usuario <> :excluido';
            $params[':excluido'] = $idExcluido;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * ¿Existe ya una cuenta con ese DNI? (integridad a nivel
     * de aplicacion: una persona, una cuenta). En edicion se
     * excluye el propio usuario del chequeo.
     */
    public function existeDni(string $dni, int $idExcluido = 0): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuario WHERE dni = :dni';
        $params = [':dni' => $dni];

        if ($idExcluido > 0) {
            $sql         .= ' AND id_usuario <> :excluido';
            $params[':excluido'] = $idExcluido;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea un usuario (la contrasena llega YA hasheada; se
     * genera con Usuario::crearHash()).
     *
     * @param array<string, mixed> $d id_rol, nombre,
     *                                apellido_paterno,
     *                                apellido_materno, dni,
     *                                usuario, password, activo
     * @return int id del nuevo usuario
     */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO usuario
                   (id_rol, nombre, apellido_paterno, apellido_materno,
                    dni, usuario, password, activo)
               VALUES
                   (:id_rol, :nombre, :ap_paterno, :ap_materno,
                    :dni, :usuario, :hash, :activo)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_rol'     => (int) $d['id_rol'],
            ':nombre'     => (string) $d['nombre'],
            ':ap_paterno' => (string) $d['apellido_paterno'],
            ':ap_materno' => (string) $d['apellido_materno'],
            ':dni'        => (string) $d['dni'],
            ':usuario'    => (string) $d['usuario'],
            ':hash'       => (string) $d['password'],
            ':activo'     => (int) $d['activo'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos de perfil de un usuario.
     * La contrasena NUNCA se toca por aqui: la define el
     * Administrador con definirPassword() (regla del sistema).
     *
     * @param array<string, mixed> $d id_rol, nombre,
     *                                apellido_paterno,
     *                                apellido_materno, dni,
     *                                usuario, activo
     */
    public function actualizar(int $id, array $d): void
    {
        $sql = 'UPDATE usuario
                  SET id_rol = :id_rol,
                      nombre = :nombre,
                      apellido_paterno = :ap_paterno,
                      apellido_materno = :ap_materno,
                      dni = :dni,
                      usuario = :usuario,
                      activo = :activo
                WHERE id_usuario = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_rol'     => (int) $d['id_rol'],
            ':nombre'     => (string) $d['nombre'],
            ':ap_paterno' => (string) $d['apellido_paterno'],
            ':ap_materno' => (string) $d['apellido_materno'],
            ':dni'        => (string) $d['dni'],
            ':usuario'    => (string) $d['usuario'],
            ':activo'     => (int) $d['activo'],
            ':id'         => $id,
        ]);
    }
}
