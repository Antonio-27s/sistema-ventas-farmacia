<?php
/**
 * ============================================================
 * MODELO: Laboratorio
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Acceso a datos de la tabla `laboratorio` (fabricas/empresas
 * que producen los productos).
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class Laboratorio
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Lista todos los laboratorios.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $sql = 'SELECT * FROM laboratorio ORDER BY nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Solo activos: para el select del formulario de producto.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarActivos(): array
    {
        $sql = 'SELECT * FROM laboratorio WHERE activo = 1 ORDER BY nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un laboratorio por su id.
     *
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql  = 'SELECT * FROM laboratorio WHERE id_laboratorio = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Crea un laboratorio.
     *
     * @param array<string, mixed> $d nombre, ruc, telefono,
     *                                direccion, correo, activo
     * @return int id del nuevo laboratorio
     */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO laboratorio
                   (nombre, ruc, telefono, direccion, correo, activo)
               VALUES
                   (:nombre, :ruc, :telefono, :direccion, :correo, :activo)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->parametros($d));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un laboratorio.
     *
     * @param array<string, mixed> $d
     */
    public function actualizar(int $id, array $d): void
    {
        $sql = 'UPDATE laboratorio
                SET nombre = :nombre, ruc = :ruc, telefono = :telefono,
                    direccion = :direccion, correo = :correo, activo = :activo
                WHERE id_laboratorio = :id';

        $stmt = $this->pdo->prepare($sql);
        $params          = $this->parametros($d);
        $params[':id']   = $id;
        $stmt->execute($params);
    }

    /**
     * ¿Ya existe un laboratorio con ese RUC? (UNIQUE en la BD).
     */
    public function existeRuc(string $ruc, int $excluirId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM laboratorio
                WHERE ruc = :ruc AND id_laboratorio <> :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ruc' => $ruc, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Normaliza los datos para el SQL (vacios = NULL).
     *
     * @param array<string, mixed> $d
     * @return array<string, mixed>
     */
    private function parametros(array $d): array
    {
        return [
            ':nombre'    => (string) $d['nombre'],
            ':ruc'       => ((string) $d['ruc'] !== '') ? (string) $d['ruc'] : null,
            ':telefono'  => ((string) $d['telefono'] !== '') ? (string) $d['telefono'] : null,
            ':direccion' => ((string) $d['direccion'] !== '') ? (string) $d['direccion'] : null,
            ':correo'    => ((string) $d['correo'] !== '') ? (string) $d['correo'] : null,
            ':activo'    => (int) $d['activo'],
        ];
    }
}
