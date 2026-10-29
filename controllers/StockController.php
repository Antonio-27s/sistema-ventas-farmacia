<?php
/**
 * ============================================================
 * CONTROLADOR: Stock (inventario)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Incremento 8.2: el stock por producto.
 *   - Listado con busqueda y filtro por estado (bajo/agotado).
 *   - Ajuste manual de cantidad (lo unico que mueve stock por
 *     ahora; compras y ventas llegaran en 8.3/8.5, en transaccion).
 *
 * Permisos (matriz RBAC, verificada por el router):
 *   Administrador y Almacenero.
 * Seguridad: token CSRF en todo POST, whitelist de acciones
 * en el router, validacion completa en PHP.
 *
 * Modo JSON (para el JS del modulo): listar y ajustar aceptan
 * &json=1 (lo consume public/js/stock/stock.js).
 */

declare(strict_types=1);

class StockController
{
    /**
     * Accion: listado de stock (default de ?controlador=Stock).
     * Con &json=1 responde el listado en JSON (lo usa el JS).
     */
    public function listar(): void
    {
        $q     = trim((string) ($_GET['q'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? '');
        $msg    = (string) ($_GET['msg'] ?? '');

        if (!in_array($estado, ['', 'BAJO', 'AGOTADO'], true)) {
            $estado = '';
        }

        $filas = (new Stock())->listar($q, $estado);

        if (isset($_GET['json'])) {
            respuestaJson([
                'total' => count($filas),
                'stock' => array_map([self::class, 'filaParaJson'], $filas),
            ]);
        }

        extract(contextoVista('Stock', 'Stock'));
        require BASE_PATH . '/views/stock/stock.php';
    }

    /**
     * Accion: ajuste de stock (POST + CSRF).
     * Con &json=1 responde {ok, cantidad, estado, fecha}
     * para el JS del modulo.
     */
    public function ajustar(): void
    {
        $url    = 'index.php?controlador=Stock';
        $esJson = isset($_GET['json']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->denegar($esJson, $url, 'Método no permitido.');
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            $this->denegar($esJson, $url, 'El formulario expiró o es inválido.');
        }

        $id   = (int) ($_POST['id_producto'] ?? 0);
        $cant = (int) ($_POST['cantidad'] ?? 0);

        if ($id <= 0 || $cant < 0 || $cant > 99999999) {
            $this->denegar($esJson, $url, 'Cantidad o producto inválido.');
        }

        $producto = (new Producto())->obtenerPorId($id);
        if ($producto === null) {
            $this->denegar($esJson, $url, 'El producto no existe.');
        }

        (new Stock())->ajustar($id, $cant);

        if ($esJson) {
            respuestaJson([
                'ok'       => true,
                'cantidad' => $cant,
                'estado'   => Stock::estadoDe($cant, (int) $producto['stock_minimo']),
                'fecha'    => date('d/m/Y H:i'),
            ]);
        }

        header('Location: ' . $url . '&msg=ajuste-ok');
        exit;
    }

    // ------------------------------------------------------------
    // Ayudas del controlador
    // ------------------------------------------------------------

    /**
     * Niega la accion y termina: responde JSON (si el JS pidio
     * &json=1) o redirige con msg=error.
     */
    private function denegar(bool $esJson, string $url, string $mensaje): void
    {
        if ($esJson) {
            respuestaJson(['ok' => false, 'error' => $mensaje]);
        }
        header('Location: ' . $url . '&msg=error');
        exit;
    }

    /**
     * Proyecta una fila del stock a los campos que el JS
     * necesita (sin datos sensibles).
     *
     * @param array<string, mixed> $s
     * @return array<string, mixed>
     */
    private static function filaParaJson(array $s): array
    {
        $cantidad = (int) $s['cantidad'];
        $minimo   = (int) $s['stock_minimo'];

        return [
            'id'        => (int) $s['id_producto'],
            'codigo'    => (string) $s['codigo'],
            'nombre'    => (string) $s['nombre'],
            'categoria' => (string) $s['categoria'],
            'cantidad'  => $cantidad,
            'minimo'    => $minimo,
            'estado'    => Stock::estadoDe($cantidad, $minimo),
            'fecha'     => $s['fecha_actualizacion'] !== null
                ? date('d/m/Y H:i', strtotime((string) $s['fecha_actualizacion']))
                : null,
        ];
    }
}
