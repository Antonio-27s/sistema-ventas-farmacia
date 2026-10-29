<?php
/**
 * ============================================================
 * MODELO: Stock
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `stock` (una fila por producto).
 * Reglas de negocio:
 *   - Sin fila o cantidad 0 = agotado.
 *   - cantidad < stock_minimo = bajo (alerta).
 *   - El stock solo cambia por este modelo: ajuste manual
 *     (8.2); compras (8.3) y ventas (8.5) llegaran siempre
 *     dentro de una transaccion.
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class Stock
{
    private PDO $pdo;

    /** Estados derivados (etiquetas para vistas y JSON). */
    public const ESTADOS = [
        'OK'      => 'Adecuado',
        'BAJO'    => 'Bajo',
        'AGOTADO' => 'Agotado',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Productos con su stock actual (listado del modulo).
     *   $busqueda : por nombre o codigo
     *   $estado   : '' (todos) | 'BAJO' | 'AGOTADO'
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $sql = 'SELECT p.id_producto, p.codigo, p.nombre, c.nombre AS categoria,
                       COALESCE(s.cantidad, 0) AS cantidad,
                       p.stock_minimo, s.fecha_actualizacion
               FROM producto p
               INNER JOIN categoria c ON c.id_categoria = p.id_categoria
               LEFT JOIN stock s ON s.id_producto = p.id_producto';

        $cond   = [];
        $params = [];

        if (trim($busqueda) !== '') {
            $cond[]         = '(p.nombre LIKE :q OR p.codigo LIKE :q)';
            $params['q']    = '%' . trim($busqueda) . '%';
        }

        if ($estado === 'BAJO') {
            $cond[] = 'COALESCE(s.cantidad, 0) > 0'
                . ' AND COALESCE(s.cantidad, 0) < p.stock_minimo';
        } elseif ($estado === 'AGOTADO') {
            $cond[] = 'COALESCE(s.cantidad, 0) = 0';
        }

        if ($cond !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $cond);
        }

        $sql .= ' ORDER BY p.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Estado derivado del stock de un producto.
     *
     * @return string 'OK' | 'BAJO' | 'AGOTADO'
     */
    public static function estadoDe(int $cantidad, int $minimo): string
    {
        if ($cantidad <= 0) {
            return 'AGOTADO';
        }
        if ($cantidad < $minimo) {
            return 'BAJO';
        }
        return 'OK';
    }

    /**
     * Ajusta (fija) la cantidad de stock de un producto.
     * Si la fila no existe aun, la crea (upsert por la UNIQUE
     * de id_producto).
     */
    public function ajustar(int $idProducto, int $cantidad): void
    {
        $sql = 'INSERT INTO stock (id_producto, cantidad)
                VALUES (:id, :cant)
                ON DUPLICATE KEY UPDATE
                    cantidad = :cant2, fecha_actualizacion = NOW()';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'    => $idProducto,
            ':cant'  => $cantidad,
            ':cant2' => $cantidad,
        ]);
    }
}
