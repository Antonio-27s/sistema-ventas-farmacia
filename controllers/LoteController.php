<?php
/**
 * ============================================================
 * CONTROLADOR: Lote (lotes y vencimientos)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Incremento 8.2: lotes con salida FEFO.
 *   - Listado FEFO (el que vence primero va primero) con busqueda.
 *   - Registro de lote: su cantidad entra al stock del producto
 *     en la misma transaccion (modelo Lote).
 *
 * Permisos (matriz RBAC, verificada por el router):
 *   Administrador y Almacenero.
 * Seguridad: token CSRF en todo POST, whitelist de acciones
 * en el router, validacion completa en PHP.
 *
 * Modo JSON (para el JS del modulo): listar acepta &json=1
 * (lo consume public/js/lote/lote.js).
 */

declare(strict_types=1);

class LoteController
{
    /**
     * Accion: listado de lotes FEFO (default de ?controlador=Lote).
     * Con &json=1 responde el listado en JSON (lo usa el JS).
     */
    public function listar(): void
    {
        $q     = trim((string) ($_GET['q'] ?? ''));
        $msg   = (string) ($_GET['msg'] ?? '');
        $filas = (new Lote())->listar($q);

        if (isset($_GET['json'])) {
            respuestaJson([
                'total' => count($filas),
                'lotes' => array_map([self::class, 'filaParaJson'], $filas),
            ]);
        }

        extract(contextoVista('Lote', 'Lotes'));
        require BASE_PATH . '/views/lote/lote.php';
    }

    /**
     * Accion: formulario de registro de lote (nuevo).
     */
    public function formLote(): void
    {
        $errores = [];
        $campos  = [
            'id_producto'       => 0,
            'numero_lote'       => '',
            'fecha_fabricacion' => '',
            'fecha_vencimiento' => '',
            'cantidad'          => 1,
        ];
        $productos = (new Producto())->listar();

        extract(contextoVista('Lote', 'Nuevo lote'));
        require BASE_PATH . '/views/lote/lote_form.php';
    }

    /**
     * Accion: registrar lote (POST + CSRF + validacion).
     * Al guardar, el stock del producto se aumenta en la
     * misma transaccion (modelo Lote::crear).
     */
    public function guardarLote(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controlador=Lote');
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: index.php?controlador=Lote&msg=error');
            exit;
        }

        $idProducto = (int) ($_POST['id_producto'] ?? 0);
        $numero     = trim((string) ($_POST['numero_lote'] ?? ''));
        $fab        = trim((string) ($_POST['fecha_fabricacion'] ?? ''));
        $venc       = trim((string) ($_POST['fecha_vencimiento'] ?? ''));
        $cant       = (int) ($_POST['cantidad'] ?? 0);

        $errores = $this->validarLote($idProducto, $numero, $fab, $venc, $cant);

        if ($errores === []) {
            (new Lote())->crear([
                'id_producto'       => $idProducto,
                'numero_lote'       => $numero,
                'fecha_fabricacion' => $fab,
                'fecha_vencimiento' => $venc,
                'cantidad'          => $cant,
            ]);

            header('Location: index.php?controlador=Lote&msg=lote-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form con lo enviado
        $campos = [
            'id_producto'       => $idProducto,
            'numero_lote'       => $numero,
            'fecha_fabricacion' => $fab,
            'fecha_vencimiento' => $venc,
            'cantidad'          => $cant,
        ];
        $productos = (new Producto())->listar();

        extract(contextoVista('Lote', 'Nuevo lote'));
        require BASE_PATH . '/views/lote/lote_form.php';
    }

    // ------------------------------------------------------------
    // Ayudas del controlador
    // ------------------------------------------------------------

    /**
     * Valida los datos del lote. Devuelve la lista de errores
     * (vacia = todo ok).
     *
     * @return string[]
     */
    private function validarLote(int $idProducto, string $numero, string $fab, string $venc, int $cant): array
    {
        $errores = [];

        if ((new Producto())->obtenerPorId($idProducto) === null) {
            $errores[] = 'Seleccione un producto válido.';
        }

        if ($numero === '') {
            $errores[] = 'El número de lote es obligatorio.';
        } elseif (mb_strlen($numero) > 100) {
            $errores[] = 'El número de lote no puede superar 100 caracteres.';
        }

        if ($venc === '' || !self::fechaValida($venc)) {
            $errores[] = 'La fecha de vencimiento es obligatoria y debe ser válida.';
        }

        if ($fab !== '' && !self::fechaValida($fab)) {
            $errores[] = 'La fecha de fabricación no es válida.';
        }

        if ($cant < 1 || $cant > 99999999) {
            $errores[] = 'La cantidad debe ser un entero positivo.';
        }

        return $errores;
    }

    /**
     * Fecha de calendario valida en formato Y-m-d (como la
     * pinta un <input type="date">).
     */
    private static function fechaValida(string $f): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $f);

        return $d !== false && $d->format('Y-m-d') === $f;
    }

    /**
     * Proyecta un lote a los campos que el JS necesita.
     *
     * @param array<string, mixed> $l
     * @return array<string, mixed>
     */
    private static function filaParaJson(array $l): array
    {
        return [
            'id'                => (int) $l['id_lote'],
            'producto'          => (string) $l['producto'],
            'codigo_producto'   => (string) $l['codigo_producto'],
            'numero_lote'       => (string) $l['numero_lote'],
            'cantidad'          => (int) $l['cantidad'],
            'fecha_fabricacion' => $l['fecha_fabricacion'] !== null
                ? date('d/m/Y', strtotime((string) $l['fecha_fabricacion'])) : null,
            'fecha_vencimiento' => date('d/m/Y', strtotime((string) $l['fecha_vencimiento'])),
            'estado'            => Lote::estadoVencimiento((string) $l['fecha_vencimiento']),
        ];
    }
}
