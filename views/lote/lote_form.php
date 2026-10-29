<?php
/**
 * VISTA: Registro de lote (modulo Lote)
 * Sistema de Ventas - Farmacia
 * Formulario de servidor (progressive enhancement: la validacion
 * en vivo de longitudes/digitos la hace validacion-forms.js).
 *
 * Variables: $productos, $campos (array: valores a re-llenar),
 *            $errores (string[]) + variables shell
 */
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app wide">
    <h1>Nuevo lote</h1>
    <p class="sub">
        Al registrar un lote, su cantidad entra al stock del
        producto en la misma operación (transacción).
    </p>

    <?php if ($errores !== []): ?>
    <div class="error">
        <?php foreach ($errores as $e): ?>
        <div>• <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form class="form-app" method="post"
          action="index.php?controlador=Lote&accion=guardar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="grid-2">
            <div class="campo">
                <label for="id_producto">Producto *</label>
                <select id="id_producto" name="id_producto" required>
                    <option value="0">— seleccione —</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= (int) $p['id_producto'] ?>"
                        <?= (int) ($campos['id_producto'] ?? 0) === (int) $p['id_producto'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(
                            (string) $p['codigo'] . ' — ' . (string) $p['nombre'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="numero_lote">Número de lote *</label>
                <input type="text" id="numero_lote" name="numero_lote" required maxlength="100"
                       value="<?= htmlspecialchars((string) ($campos['numero_lote'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="fecha_fabricacion">Fecha de fabricación</label>
                <input type="date" id="fecha_fabricacion" name="fecha_fabricacion"
                       value="<?= htmlspecialchars(
                           (string) ($campos['fecha_fabricacion'] ?? ''),
                           ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="fecha_vencimiento">Fecha de vencimiento *</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required
                       value="<?= htmlspecialchars(
                           (string) ($campos['fecha_vencimiento'] ?? ''),
                           ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="cantidad">Cantidad (entra al stock) *</label>
                <input type="number" id="cantidad" name="cantidad" min="1" step="1" required
                       value="<?= (int) ($campos['cantidad'] ?? 1) ?>">
            </div>
        </div>

        <div class="botones-app">
            <button type="submit" class="btn-sm primario">Registrar lote</button>
            <a class="btn-sm" href="index.php?controlador=Lote">Volver a lotes</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
