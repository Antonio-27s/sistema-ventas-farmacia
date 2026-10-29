<?php
/**
 * ============================================================
 * CONTROLADOR: Producto (catalogo)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Incremento 8.1: el catalogo de la farmacia.
 *   - Catalogo: listado con busqueda y filtro por categoria,
 *     crear/editar producto, activar/desactivar.
 *   - Categorias y laboratorios: listado + crear/editar
 *     (las tablas base del catalogo).
 *
 * Permisos (matriz RBAC, verificada por el router):
 *   Administrador y Almacenero.
 * Seguridad: token CSRF en todo POST, whitelist de acciones
 * en el router, validacion completa en PHP (la BD es la
 * segunda capa: UNIQUE codigo/nombre/ruc y FK).
 *
 * Modo JSON (para el JS del modulo): las acciones listar y
 * toggle_estado aceptan &json=1 y responden con JSON (lo
 * consume public/js/producto/catalogo.js). Sin el parametro
 * se renderiza la vista / se redirige (funciona sin JS).
 */

declare(strict_types=1);

class ProductoController
{
    /**
     * Accion: catalogo (default de ?controlador=Producto).
     * Con &json=1 responde el listado en JSON (lo usa el JS).
     */
    public function listar(): void
    {
        $q     = trim((string) ($_GET['q'] ?? ''));
        $idCat = (int) ($_GET['id_categoria'] ?? 0);
        $msg   = (string) ($_GET['msg'] ?? '');
        $productos  = (new Producto())->listar($q, $idCat);
        $categorias = (new Categoria())->listar();

        if (isset($_GET['json'])) {
            respuestaJson([
                'total'     => count($productos),
                'productos' => array_map([self::class, 'productoParaJson'], $productos),
            ]);
        }

        extract(contextoVista('Producto', 'Catálogo de productos'));
        require BASE_PATH . '/views/producto/producto_catalogo.php';
    }

    /**
     * Accion: formulario de producto (nuevo, o edicion con &id).
     */
    public function formProducto(): void
    {
        $id       = (int) ($_GET['id'] ?? 0);
        $producto = $id > 0 ? (new Producto())->obtenerPorId($id) : null;

        if ($id > 0 && $producto === null) {
            header('Location: index.php?controlador=Producto&msg=error');
            exit;
        }

        $errores = [];
        // Valores a re-llenar: edicion = datos existentes;
        // nuevo = vacios (al volver tras error, los del POST).
        $campos = $producto ?? [
            'codigo' => '', 'nombre' => '', 'principio_activo' => '',
            'concentracion' => '', 'presentacion' => '',
            'tipo_producto' => 'MEDICAMENTO', 'requiere_receta' => 0,
            'precio_compra' => '0.00', 'precio_venta' => '0.00',
            'stock_minimo' => 0, 'id_categoria' => 0,
            'id_laboratorio' => 0, 'activo' => 1,
        ];

        $categorias   = (new Categoria())->listar();
        $laboratorios = (new Laboratorio())->listarActivos();

        extract(contextoVista('Producto', $id > 0 ? 'Editar producto' : 'Nuevo producto'));
        require BASE_PATH . '/views/producto/producto_form.php';
    }

    /**
     * Accion: guardar producto (POST + CSRF + validacion).
     */
    public function guardarProducto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controlador=Producto');
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: index.php?controlador=Producto&msg=error');
            exit;
        }

        $id      = (int) ($_POST['id_producto'] ?? 0);
        $errores = $this->validarProducto($_POST, $id);

        if ($errores === []) {
            $modelo = new Producto();
            $d      = $this->normalizarProducto($_POST);

            if ($id > 0) {
                $modelo->actualizar($id, $d);
            } else {
                $modelo->crear($d);
            }

            header('Location: index.php?controlador=Producto&msg=producto-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form con lo enviado
        $producto     = null;
        $campos       = $this->normalizarProducto($_POST);
        $categorias   = (new Categoria())->listar();
        $laboratorios = (new Laboratorio())->listarActivos();

        extract(contextoVista('Producto', $id > 0 ? 'Editar producto' : 'Nuevo producto'));
        require BASE_PATH . '/views/producto/producto_form.php';
    }

    /**
     * Accion: activar/desactivar un producto (POST + CSRF).
     * Con &json=1 responde {ok, activo} para el JS del catalogo.
     */
    public function toggleEstado(): void
    {
        $url    = 'index.php?controlador=Producto';
        $esJson = isset($_GET['json']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->denegar($esJson, $url, 'Método no permitido.');
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            $this->denegar($esJson, $url, 'El formulario expiró o es inválido.');
        }

        $id       = (int) ($_POST['producto_id'] ?? 0);
        $modelo   = new Producto();
        $producto = $id > 0 ? $modelo->obtenerPorId($id) : null;

        if ($producto === null) {
            $this->denegar($esJson, $url, 'El producto no existe o no es válido.');
        }

        $nuevoActivo = (int) $producto['activo'] !== 1 ? 1 : 0;
        $modelo->cambiarEstado($id, $nuevoActivo === 1);

        if ($esJson) {
            respuestaJson(['ok' => true, 'activo' => $nuevoActivo]);
        }

        header('Location: ' . $url . '&msg=estado-ok');
        exit;
    }

    /**
     * Accion: categorias (listado + crear/editar).
     */
    public function categorias(): void
    {
        $modelo = new Categoria();
        $id     = (int) ($_GET['id'] ?? 0);
        $editar = $id > 0 ? $modelo->obtenerPorId($id) : null;

        if ($id > 0 && $editar === null) {
            $id = 0; // id inexistente en la URL: modo crear
        }

        $lista  = $modelo->listar();
        $campos = $editar !== null
            ? ['nombre' => (string) $editar['nombre'],
               'descripcion' => (string) ($editar['descripcion'] ?? '')]
            : ['nombre' => '', 'descripcion' => ''];
        $error  = null;
        $msg    = (string) ($_GET['msg'] ?? '');

        extract(contextoVista('Producto', 'Categorías'));
        require BASE_PATH . '/views/producto/producto_categorias.php';
    }

    /**
     * Accion: guardar categoria (POST + CSRF + validacion).
     */
    public function guardarCategoria(): void
    {
        $url = 'index.php?controlador=Producto&accion=categorias';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $url);
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: ' . $url);
            exit;
        }

        $id          = (int) ($_POST['id_categoria'] ?? 0);
        $nombre      = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $campos      = ['nombre' => $nombre, 'descripcion' => $descripcion];
        $error       = null;

        if ($nombre === '') {
            $error = 'El nombre de la categoría es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $error = 'El nombre no puede superar 100 caracteres.';
        } elseif (mb_strlen($descripcion) > 255) {
            $error = 'La descripción no puede superar 255 caracteres.';
        } elseif ((new Categoria())->existeNombre($nombre, $id)) {
            $error = 'Ya existe una categoría con ese nombre.';
        } else {
            $modelo = new Categoria();

            if ($id > 0) {
                $modelo->actualizar($id, $nombre, $descripcion);
            } else {
                $modelo->crear($nombre, $descripcion);
            }

            header('Location: ' . $url . '&msg=cat-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form con lo enviado
        $lista = (new Categoria())->listar();
        $msg   = '';

        extract(contextoVista('Producto', 'Categorías'));
        require BASE_PATH . '/views/producto/producto_categorias.php';
    }

    /**
     * Accion: laboratorios (listado + crear/editar).
     */
    public function laboratorios(): void
    {
        $modelo = new Laboratorio();
        $id     = (int) ($_GET['id'] ?? 0);
        $editar = $id > 0 ? $modelo->obtenerPorId($id) : null;

        if ($id > 0 && $editar === null) {
            $id = 0; // id inexistente en la URL: modo crear
        }

        $lista  = $modelo->listar();
        $campos = $editar !== null
            ? ['nombre' => (string) $editar['nombre'],
               'ruc' => (string) ($editar['ruc'] ?? ''),
               'telefono' => (string) ($editar['telefono'] ?? ''),
               'direccion' => (string) ($editar['direccion'] ?? ''),
               'correo' => (string) ($editar['correo'] ?? ''),
               'activo' => (int) $editar['activo']]
            : ['nombre' => '', 'ruc' => '', 'telefono' => '',
               'direccion' => '', 'correo' => '', 'activo' => 1];
        $error  = null;
        $msg    = (string) ($_GET['msg'] ?? '');

        extract(contextoVista('Producto', 'Laboratorios'));
        require BASE_PATH . '/views/producto/producto_laboratorios.php';
    }

    /**
     * Accion: guardar laboratorio (POST + CSRF + validacion).
     */
    public function guardarLaboratorio(): void
    {
        $url = 'index.php?controlador=Producto&accion=laboratorios';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $url);
            exit;
        }

        $csrf = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals(UsuarioController::csrfToken(), $csrf)) {
            header('Location: ' . $url);
            exit;
        }

        $id        = (int) ($_POST['id_laboratorio'] ?? 0);
        $nombre    = trim((string) ($_POST['nombre'] ?? ''));
        $ruc       = trim((string) ($_POST['ruc'] ?? ''));
        $telefono  = trim((string) ($_POST['telefono'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $correo    = trim((string) ($_POST['correo'] ?? ''));
        $activo    = isset($_POST['activo']) ? 1 : 0;

        $campos = ['nombre' => $nombre, 'ruc' => $ruc, 'telefono' => $telefono,
                   'direccion' => $direccion, 'correo' => $correo,
                   'activo' => $activo];
        $error  = null;

        if ($nombre === '') {
            $error = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 150) {
            $error = 'El nombre no puede superar 150 caracteres.';
        } elseif ($ruc !== ''
                && (mb_strlen($ruc) !== 11 || !ctype_digit($ruc))) {
            $error = 'El RUC debe tener exactamente 11 dígitos.';
        } elseif (mb_strlen($telefono) > 20) {
            $error = 'El teléfono no puede superar 20 caracteres.';
        } elseif (mb_strlen($direccion) > 200) {
            $error = 'La dirección no puede superar 200 caracteres.';
        } elseif (mb_strlen($correo) > 150) {
            $error = 'El correo no puede superar 150 caracteres.';
        } elseif ($ruc !== '' && (new Laboratorio())->existeRuc($ruc, $id)) {
            $error = 'Ya existe un laboratorio con ese RUC.';
        } else {
            $d = [
                'nombre'    => $nombre,
                'ruc'       => $ruc !== '' ? $ruc : '',
                'telefono'  => $telefono,
                'direccion' => $direccion,
                'correo'    => $correo,
                'activo'    => $activo,
            ];
            $modelo = new Laboratorio();

            if ($id > 0) {
                $modelo->actualizar($id, $d);
            } else {
                $modelo->crear($d);
            }

            header('Location: ' . $url . '&msg=lab-ok');
            exit;
        }

        // Validacion fallida: re-llenar el form con lo enviado
        $lista = (new Laboratorio())->listar();
        $msg   = '';

        extract(contextoVista('Producto', 'Laboratorios'));
        require BASE_PATH . '/views/producto/producto_laboratorios.php';
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
     * Valida los datos del producto. Devuelve la lista de
     * errores (vacia = todo ok).
     *
     * @param array<string, mixed> $p
     * @return string[]
     */
    private function validarProducto(array $p, int $id): array
    {
        $errores = [];
        $nombre  = trim((string) ($p['nombre'] ?? ''));
        $codigo  = trim((string) ($p['codigo'] ?? ''));
        $tipo    = (string) ($p['tipo_producto'] ?? '');
        $idCat   = (int) ($p['id_categoria'] ?? 0);
        $idLab   = (int) ($p['id_laboratorio'] ?? 0);
        $pa      = trim((string) ($p['principio_activo'] ?? ''));
        $conc    = trim((string) ($p['concentracion'] ?? ''));
        $pres    = trim((string) ($p['presentacion'] ?? ''));
        $pc      = trim((string) ($p['precio_compra'] ?? ''));
        $pv      = trim((string) ($p['precio_venta'] ?? ''));
        $sm      = (int) ($p['stock_minimo'] ?? 0);

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 150) {
            $errores[] = 'El nombre no puede superar 150 caracteres.';
        }

        if ($codigo === '') {
            $errores[] = 'El código es obligatorio.';
        } elseif (mb_strlen($codigo) > 50) {
            $errores[] = 'El código no puede superar 50 caracteres.';
        } elseif ((new Producto())->existeCodigo($codigo, $id)) {
            $errores[] = 'Ya existe un producto con ese código.';
        }

        if ($idCat <= 0 || (new Categoria())->obtenerPorId($idCat) === null) {
            $errores[] = 'Seleccione una categoría válida.';
        }

        if ($idLab > 0 && (new Laboratorio())->obtenerPorId($idLab) === null) {
            $errores[] = 'El laboratorio seleccionado no existe.';
        }

        if (!in_array($tipo, Producto::TIPOS_PRODUCTO, true)) {
            $errores[] = 'El tipo de producto no es válido.';
        }

        if ($pa !== '' && mb_strlen($pa) > 150) {
            $errores[] = 'El principio activo no puede superar 150 caracteres.';
        }

        if ($conc !== '' && mb_strlen($conc) > 100) {
            $errores[] = 'La concentración no puede superar 100 caracteres.';
        }

        if ($pres !== '' && mb_strlen($pres) > 100) {
            $errores[] = 'La presentación no puede superar 100 caracteres.';
        }

        if (!is_numeric($pc) || (float) $pc < 0 || (float) $pc > 99999999.99) {
            $errores[] = 'El precio de compra debe ser un número positivo.';
        }

        if (!is_numeric($pv) || (float) $pv < 0 || (float) $pv > 99999999.99) {
            $errores[] = 'El precio de venta debe ser un número positivo.';
        }

        if ($sm < 0) {
            $errores[] = 'El stock mínimo no puede ser negativo.';
        }

        return $errores;
    }

    /**
     * Normaliza los datos del POST para el modelo (o para
     * re-llenar el formulario tras un error).
     *
     * @param array<string, mixed> $p
     * @return array<string, mixed>
     */
    private function normalizarProducto(array $p): array
    {
        return [
            'codigo'           => trim((string) ($p['codigo'] ?? '')),
            'nombre'           => trim((string) ($p['nombre'] ?? '')),
            'principio_activo' => trim((string) ($p['principio_activo'] ?? '')),
            'concentracion'    => trim((string) ($p['concentracion'] ?? '')),
            'presentacion'     => trim((string) ($p['presentacion'] ?? '')),
            'tipo_producto'    => (string) ($p['tipo_producto'] ?? 'MEDICAMENTO'),
            'requiere_receta'  => isset($p['requiere_receta']) ? 1 : 0,
            'precio_compra'    => $this->precio((string) ($p['precio_compra'] ?? '')),
            'precio_venta'     => $this->precio((string) ($p['precio_venta'] ?? '')),
            'stock_minimo'     => max(0, (int) ($p['stock_minimo'] ?? 0)),
            'id_categoria'     => (int) ($p['id_categoria'] ?? 0),
            'id_laboratorio'   => (int) ($p['id_laboratorio'] ?? 0),
            'activo'           => isset($p['activo']) ? 1 : 0,
        ];
    }

    /**
     * Convierte el precio a formato DECIMAL(10,2) ("12.50");
     * acepta coma decimal. Si no es numerico devuelve "0.00"
     * (la validacion ya se encargo de reportarlo).
     */
    private function precio(string $v): string
    {
        $v = str_replace(',', '.', trim($v));

        return is_numeric($v) ? number_format((float) $v, 2, '.', '') : '0.00';
    }

    /**
     * Proyecta una fila del catalogo a los campos que el JS
     * necesita (sin datos sensibles: el hash nunca sale, y el
     * stock/precios son publicos dentro del sistema).
     *
     * @param array<string, mixed> $p
     * @return array<string, mixed>
     */
    private static function productoParaJson(array $p): array
    {
        return [
            'id'               => (int) $p['id_producto'],
            'codigo'           => (string) $p['codigo'],
            'nombre'           => (string) $p['nombre'],
            'principio_activo' => (string) $p['principio_activo'],
            'categoria'        => (string) $p['categoria'],
            'laboratorio'      => $p['laboratorio'] !== null
                ? (string) $p['laboratorio'] : null,
            'tipo'             => (string) $p['tipo_producto'],
            'tipo_label'       => (string) (Producto::TIPOS_LABEL[(string) $p['tipo_producto']]
                ?? (string) $p['tipo_producto']),
            'requiere_receta'  => (int) $p['requiere_receta'] === 1,
            'precio_venta'     => (float) $p['precio_venta'],
            'stock'            => (int) $p['stock'],
            'activo'           => (int) $p['activo'] === 1,
        ];
    }
}
