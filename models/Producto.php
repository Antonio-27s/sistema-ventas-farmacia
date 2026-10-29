<?php
/**
 * ============================================================
 * MODELO: Producto
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `producto` (+ su categoria,
 * laboratorio y stock actual para el listado del catalogo).
 * El controlador valida y normaliza los datos ANTES de crear
 * o actualizar; la BD es la segunda capa (UNIQUE codigo, FK).
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class Producto
{
    private PDO $pdo;

    /** Tipos validos (deben coincidir con el ENUM de la BD). */
    public const TIPOS_PRODUCTO = [
        'MEDICAMENTO', 'DISPOSITIVO_MEDICO', 'HIGIENE',
        'CUIDADO_PERSONAL', 'SUPLEMENTO', 'OTRO',
    ];

    /**
     * Etiquetas amigables de los tipos. Fuente unica: la usan
     * las vistas, el JSON del catalogo y (en su momento) el JS.
     */
    public const TIPOS_LABEL = [
        'MEDICAMENTO'      => 'Medicamento',
        'DISPOSITIVO_MEDICO' => 'Dispositivo médico',
        'HIGIENE'          => 'Higiene',
        'CUIDADO_PERSONAL' => 'Cuidado personal',
        'SUPLEMENTO'       => 'Suplemento',
        'OTRO'             => 'Otro',
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Lista el catalogo con filtros:
     *   $busqueda    : busca en nombre, codigo y principio activo
     *   $idCategoria : solo esa categoria (0 = todas)
     * Devuelve p.* + categoria + laboratorio + stock (cantidad).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(string $busqueda = '', int $idCategoria = 0): array
    {
        $sql = 'SELECT p.*, c.nombre AS categoria, l.nombre AS laboratorio,
                       COALESCE(s.cantidad, 0) AS stock
               FROM producto p
               INNER JOIN categoria c ON c.id_categoria = p.id_categoria
               LEFT JOIN laboratorio l ON l.id_laboratorio = p.id_laboratorio
               LEFT JOIN stock s ON s.id_producto = p.id_producto';

        $cond   = [];
        $params = [];

        if (trim($busqueda) !== '') {
            $cond[]         = '(p.nombre LIKE :q OR p.codigo LIKE :q'
                . ' OR p.principio_activo LIKE :q)';
            $params['q']    = '%' . trim($busqueda) . '%';
        }

        if ($idCategoria > 0) {
            $cond[]         = 'p.id_categoria = :cat';
            $params['cat']  = $idCategoria;
        }

        if ($cond !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $cond);
        }

        $sql .= ' ORDER BY p.activo DESC, p.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un producto por su id.
     *
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql  = 'SELECT * FROM producto WHERE id_producto = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Crea un producto completo.
     *
     * @param array<string, mixed> $d datos normalizados
     * @return int id del nuevo producto
     */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO producto
                   (codigo, nombre, principio_activo, concentracion,
                    presentacion, tipo_producto, requiere_receta,
                    precio_compra, precio_venta, stock_minimo,
                    id_categoria, id_laboratorio, activo)
               VALUES
                   (:codigo, :nombre, :pa, :conc, :pres, :tipo, :rr,
                    :pc, :pv, :sm, :cat, :lab, :activo)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->parametros($d));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un producto.
     *
     * @param array<string, mixed> $d datos normalizados
     */
    public function actualizar(int $id, array $d): void
    {
        $sql = 'UPDATE producto SET
                   codigo = :codigo, nombre = :nombre,
                   principio_activo = :pa, concentracion = :conc,
                   presentacion = :pres, tipo_producto = :tipo,
                   requiere_receta = :rr, precio_compra = :pc,
                   precio_venta = :pv, stock_minimo = :sm,
                   id_categoria = :cat, id_laboratorio = :lab,
                   activo = :activo
               WHERE id_producto = :id';

        $stmt   = $this->pdo->prepare($sql);
        $params = $this->parametros($d);
        $params[':id'] = $id;
        $stmt->execute($params);
    }

    /**
     * ¿Ya existe un producto con ese codigo? (UNIQUE en la BD;
     * $excluirId permite el modo edicion).
     */
    public function existeCodigo(string $codigo, int $excluirId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM producto
                WHERE codigo = :codigo AND id_producto <> :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Activa/desactiva un producto (no borra: preserva historial).
     */
    public function cambiarEstado(int $id, bool $activo): void
    {
        $sql = 'UPDATE producto SET activo = :activo WHERE id_producto = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    /**
     * Normaliza los datos para el SQL (opcionales vacios = NULL;
     * precios como texto DECIMAL).
     *
     * @param array<string, mixed> $d
     * @return array<string, mixed>
     */
    private function parametros(array $d): array
    {
        return [
            ':codigo' => (string) $d['codigo'],
            ':nombre' => (string) $d['nombre'],
            ':pa'     => ((string) $d['principio_activo'] !== '')
                ? (string) $d['principio_activo'] : null,
            ':conc'   => ((string) $d['concentracion'] !== '')
                ? (string) $d['concentracion'] : null,
            ':pres'   => ((string) $d['presentacion'] !== '')
                ? (string) $d['presentacion'] : null,
            ':tipo'   => (string) $d['tipo_producto'],
            ':rr'     => (int) $d['requiere_receta'],
            ':pc'     => (string) $d['precio_compra'],
            ':pv'     => (string) $d['precio_venta'],
            ':sm'     => (int) $d['stock_minimo'],
            ':cat'    => (int) $d['id_categoria'],
            ':lab'    => ((int) $d['id_laboratorio'] > 0)
                ? (int) $d['id_laboratorio'] : null,
            ':activo' => (int) $d['activo'],
        ];
    }
}
