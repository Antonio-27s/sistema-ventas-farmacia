<?php
/**
 * VISTA: Formulario de producto (nuevo o edicion)
 * Sistema de Ventas - Farmacia
 * Variables: $producto (array|null: existente; null = nuevo),
 *            $campos (array: valores a re-llenar),
 *            $errores (string[]), $categorias, $laboratorios,
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$esEdicion = $producto !== null;
$csrf = UsuarioController::csrfToken();
$v = static function (string $campo) use ($campos): string {
    return (string) ($campos[$campo] ?? '');
};
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app wide">
    <h1><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>
    <p class="sub">
        <?= $esEdicion
            ? 'Código ' . htmlspecialchars((string) $producto['codigo'], ENT_QUOTES, 'UTF-8')
            : 'Complete los datos del producto para el catálogo.' ?>
    </p>

    <?php if ($errores !== []): ?>
    <div class="error">
        <?php foreach ($errores as $e): ?>
        <div>• <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form class="form-app" method="post"
          action="index.php?controlador=Producto&accion=guardar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_producto"
               value="<?= $esEdicion ? (int) $producto['id_producto'] : 0 ?>">

        <div class="grid-2">
            <div class="campo">
                <label for="codigo">Código *</label>
                <input type="text" id="codigo" name="codigo" required maxlength="50"
                       value="<?= htmlspecialchars($v('codigo'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="150"
                       value="<?= htmlspecialchars($v('nombre'), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="id_categoria">Categoría *</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="0">— seleccione —</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id_categoria'] ?>"
                        <?= (int) $v('id_categoria') === (int) $c['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $c['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="id_laboratorio">Laboratorio</label>
                <select id="id_laboratorio" name="id_laboratorio">
                    <option value="0">— sin laboratorio —</option>
                    <?php foreach ($laboratorios as $l): ?>
                    <option value="<?= (int) $l['id_laboratorio'] ?>"
                        <?= (int) $v('id_laboratorio') === (int) $l['id_laboratorio'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $l['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="tipo_producto">Tipo de producto *</label>
                <select id="tipo_producto" name="tipo_producto" required>
                    <?php foreach (Producto::TIPOS_PRODUCTO as $t): ?>
                    <option value="<?= $t ?>" <?= $v('tipo_producto') === $t ? 'selected' : '' ?>>
                        <?= Producto::TIPOS_LABEL[$t] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="presentacion">Presentación</label>
                <input type="text" id="presentacion" name="presentacion" maxlength="100"
                       value="<?= htmlspecialchars($v('presentacion'), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ej: Caja x 10 comprimidos">
            </div>

            <div class="campo">
                <label for="principio_activo">Principio activo</label>
                <input type="text" id="principio_activo" name="principio_activo" maxlength="150"
                       value="<?= htmlspecialchars($v('principio_activo'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="concentracion">Concentración</label>
                <input type="text" id="concentracion" name="concentracion" maxlength="100"
                       value="<?= htmlspecialchars($v('concentracion'), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ej: 500 mg">
            </div>

            <div class="campo">
                <label for="precio_compra">Precio compra (S/)</label>
                <input type="number" id="precio_compra" name="precio_compra" min="0" step="0.01"
                       required value="<?= htmlspecialchars($v('precio_compra'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="precio_venta">Precio venta (S/)</label>
                <input type="number" id="precio_venta" name="precio_venta" min="0" step="0.01"
                       required value="<?= htmlspecialchars($v('precio_venta'), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="stock_minimo">Stock mínimo</label>
                <input type="number" id="stock_minimo" name="stock_minimo" min="0" step="1"
                       value="<?= htmlspecialchars($v('stock_minimo'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <div class="check-claro">
                    <input type="checkbox" id="requiere_receta" name="requiere_receta"
                        <?= (int) $v('requiere_receta') === 1 ? 'checked' : '' ?>>
                    <label for="requiere_receta">Requiere receta médica</label>
                </div>
                <div class="check-claro">
                    <input type="checkbox" id="activo" name="activo"
                        <?= (int) $v('activo') === 1 ? 'checked' : '' ?>>
                    <label for="activo">Activo (visible en el sistema)</label>
                </div>
            </div>
        </div>

        <div class="botones-app">
            <button type="submit" class="btn-sm primario">Guardar producto</button>
            <a class="btn-sm" href="index.php?controlador=Producto">Volver al catálogo</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
