<?php
/**
 * ============================================================
 * MODELO: Categoria
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `categoria` (grupos del catalogo
 * que usan los productos).
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class Categoria
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Lista todas las categorias con la cantidad de productos
     * que tienen (para la vista de gestion).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $sql = 'SELECT c.id_categoria, c.nombre, c.descripcion,
                       (SELECT COUNT(*) FROM producto p
                        WHERE p.id_categoria = c.id_categoria) AS total_productos
               FROM categoria c
               ORDER BY c.nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una categoria por su id.
     *
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql  = 'SELECT * FROM categoria WHERE id_categoria = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Crea una categoria.
     *
     * @return int id de la nueva categoria
     */
    public function crear(string $nombre, ?string $descripcion): int
    {
        $sql = 'INSERT INTO categoria (nombre, descripcion)
                VALUES (:nombre, :descripcion)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza nombre/descripcion de una categoria.
     */
    public function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $sql = 'UPDATE categoria
                SET nombre = :nombre, descripcion = :descripcion
                WHERE id_categoria = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
            ':id'          => $id,
        ]);
    }

    /**
     * ¿Ya existe una categoria con ese nombre? (UNIQUE en la BD;
     * $excluirId permite el modo edicion).
     */
    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM categoria
                WHERE nombre = :nombre AND id_categoria <> :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
