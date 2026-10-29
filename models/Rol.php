<?php
/**
 * ============================================================
 * MODELO: Rol
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Regla de acceso (RBAC): define QUE modulo (controlador)
 * puede entrar CADA rol.
 *
 * - Denegar por defecto: un rol solo entra a lo que esta
 *   explicitamente listado en MODULOS_POR_ROL.
 * - Cuando se implemente un modulo nuevo, debe agregarse a la
 *   matriz (y a la matriz de cada rol que deba acceder).
 * - Mientras un modulo no este implementado, el router muestra
 *   la vista "en construccion" (si el rol tiene permiso).
 *
 * Mapa de modulos del shell:
 *   - 'Inicio'   : home comun (dashboard) de todos los roles.
 *   - 'Usuario'  : mi perfil + sesion + cierre (esta en header).
 *   - 'Usuarios' : gestion de cuentas, solo Administrador.
 */

declare(strict_types=1);

class Rol
{
    private PDO $pdo;

    /**
     * REGLA DE NEGOCIO: matriz de modulos por rol.
     *
     * 'Inicio' es el home (dashboard); 'Usuario' = mi perfil;
     * 'Usuarios' = gestion (solo Administrador). El resto de
     * los modulos es el panel de ventas (incremental).
     */
    public const MODULOS_POR_ROL = [
        'Administrador' => [
            'Inicio', 'Usuario', 'Usuarios',
            'Venta', 'Cliente', 'Receta', 'Caja',
            'Compra', 'Producto', 'Stock', 'Lote', 'Reporte',
        ],
        'Vendedor' => [
            'Inicio', 'Usuario',
            'Venta', 'Cliente', 'Receta', 'Caja',
        ],
        'Almacenero' => [
            'Inicio', 'Usuario',
            'Compra', 'Producto', 'Stock', 'Lote',
        ],
    ];

    /** Modulo donde aterriza cada rol al iniciar sesion. */
    public const INICIO_POR_ROL = [
        'Administrador' => 'Inicio',
        'Vendedor'      => 'Inicio',
        'Almacenero'    => 'Inicio',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Nombre del rol por su id (siempre desde la BD: es la
     * fuente de verdad; la sesion solo almacena el id).
     *
     * @return string|null
     */
    public function nombrePorId(int $idRol): ?string
    {
        $sql  = 'SELECT nombre FROM rol WHERE id_rol = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idRol]);
        $nombre = $stmt->fetchColumn();

        return $nombre === false ? null : (string) $nombre;
    }

    /**
     * Lista todos los roles (para el select del formulario
     * de usuarios; siempre desde la BD).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $sql  = 'SELECT id_rol, nombre, descripcion FROM rol ORDER BY nombre ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * ¿Puede este rol entrar a este modulo? (denegar por defecto)
     */
    public static function puedeAcceder(string $rolNombre, string $modulo): bool
    {
        $modulos = self::MODULOS_POR_ROL[$rolNombre] ?? [];

        return in_array($modulo, $modulos, true);
    }

    /**
     * Modulo de inicio del rol (destino del login y de "mi panel").
     */
    public static function inicio(string $rolNombre): string
    {
        return self::INICIO_POR_ROL[$rolNombre] ?? 'Inicio';
    }

    /**
     * Modulos para el aside: los modulos del rol, menos los que
     * ya vive el header ('Inicio' y 'Usuario' = "Ver perfil").
     */
    public static function modulosParaAside(string $rolNombre): array
    {
        return array_values(array_diff(
            self::MODULOS_POR_ROL[$rolNombre] ?? [],
            ['Inicio', 'Usuario']
        ));
    }
}
