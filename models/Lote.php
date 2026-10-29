<?php
/**
 * ============================================================
 * MODELO: Lote
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `lote` (partidas de producto con
 * fabricacion y vencimiento).
 * Reglas de negocio:
 *   - FEFO (first expired, first out): el listado se ordena
 *     por fecha_vencimiento ASC (el que vence antes, sale antes).
 *   - Registrar un lote aumenta el stock del producto en la
 *     MISMA transaccion (todo o nada).
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class Lote
{
    private PDO $pdo;

    /** Lotes con vencimiento a N dias o menos se marcan PRONTO. */
    public const DIAS_PRONTO = 30;

    /** Estados derivados (etiquetas para vistas y JSON). */
    public const ESTADOS = [
        'VENCIDO' => 'Vencido',
        'PRONTO'  => 'Vence pronto',
        'VIGENTE' => 'Vigente',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Lotes con su producto, ordenados FEFO (el que vence
     * primero va primero).
     *   $busqueda : por numero de lote, nombre o codigo
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(string $busqueda = ''): array
    {
        $sql = 'SELECT l.*, p.nombre AS producto, p.codigo AS codigo_producto
               FROM lote l
               INNER JOIN producto p ON p.id_producto = l.id_producto';

        $params = [];
        if (trim($busqueda) !== '') {
            $sql .= ' WHERE (l.numero_lote LIKE :q
                      OR p.nombre LIKE :q OR p.codigo LIKE :q)';
            $params['q'] = '%' . trim($busqueda) . '%';
        }

        // FEFO: primero vence, primero sale
        $sql .= ' ORDER BY l.fecha_vencimiento ASC, l.id_lote ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Estado derivado por vencimiento de un lote.
     *
     * @return string 'VENCIDO' | 'PRONTO' | 'VIGENTE'
     */
    public static function estadoVencimiento(string $fechaVencimiento): string
    {
        $vence = strtotime($fechaVencimiento);
        $hoy   = strtotime(date('Y-m-d'));

        if ($vence < $hoy) {
            return 'VENCIDO';
        }

        $dias = (int) floor(($vence - $hoy) / 86400);
        return $dias <= self::DIAS_PRONTO ? 'PRONTO' : 'VIGENTE';
    }

    /**
     * Registra un lote y aumenta el stock del producto en la
     * misma transaccion (atomicidad: o entra el lote con su
     * stock, o no pasa nada).
     *
     * @param array<string, mixed> $d id_producto, numero_lote,
     *                                fecha_fabricacion,
     *                                fecha_vencimiento, cantidad
     * @return int id del nuevo lote
     */
    public function crear(array $d): int
    {
        $this->pdo->beginTransaction();

        try {
            $sql = 'INSERT INTO lote
                       (id_producto, numero_lote, fecha_fabricacion,
                        fecha_vencimiento, cantidad)
                   VALUES
                       (:id_producto, :numero, :fab, :venc, :cant)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_producto' => (int) $d['id_producto'],
                ':numero'      => (string) $d['numero_lote'],
                ':fab'         => $d['fecha_fabricacion'] !== ''
                    ? (string) $d['fecha_fabricacion'] : null,
                ':venc'        => (string) $d['fecha_vencimiento'],
                ':cant'        => (int) $d['cantidad'],
            ]);
            $idLote = (int) $this->pdo->lastInsertId();

            // La cantidad del lote entra al stock del producto
            $sql2 = 'INSERT INTO stock (id_producto, cantidad)
                     VALUES (:id, :cant)
                     ON DUPLICATE KEY UPDATE
                         cantidad = cantidad + :cant2,
                         fecha_actualizacion = NOW()';

            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([
                ':id'    => (int) $d['id_producto'],
                ':cant'  => (int) $d['cantidad'],
                ':cant2' => (int) $d['cantidad'],
            ]);

            $this->pdo->commit();
            return $idLote;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
