<?php
/**
 * VISTA: Categorias (modulo Producto)
 * Sistema de Ventas - Farmacia
 * Variables: $lista, $id (0 = crear; >0 = editar), $campos
 *            (nombre/descripcion a re-llenar), $error (string|null),
 *            $msg (string) + variables shell
 */
$textosMsg = [
    'cat-ok' => 'Categoría guardada correctamente.',
    'error'  => 'No se pudo completar la acción.',
];
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Categorías</h1>
            <p class="sub">Grupos del catálogo que usan los productos.</p>
        </div>
        <div class="botones-app sin-margen">
            <a class="btn-sm" href="index.php?controlador=Producto">← Catálogo</a>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
    <div class="aviso<?= $msg === 'error' ? '' : ' ok' ?>">
        <?= htmlspecialchars((string) ($textosMsg[$msg] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="panel-grid">
        <div class="tabla-scroll">
            <table class="tabla">
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Productos</th>
                    <th></th>
                </tr>
                <?php if ($lista === []): ?>
                <tr>
                    <td colspan="4" class="vacio">
                        Aún no hay categorías. Cree la primera en el formulario.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($lista as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars((string) $c['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars((string) ($c['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $c['total_productos'] ?></td>
                    <td class="celda-acciones">
                        <a class="btn-sm"
                           href="index.php?controlador=Producto&accion=categorias&id=<?= (int) $c['id_categoria'] ?>">
                            Editar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <form class="form-app" method="post"
              action="index.php?controlador=Producto&accion=guardar_categoria">
            <h3 class="form-titulo"><?= $id > 0 ? 'Editar categoría' : 'Nueva categoría' ?></h3>

            <?php if ($error !== null): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_categoria" value="<?= $id ?>">

            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100"
                       value="<?= htmlspecialchars((string) ($campos['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" maxlength="255"
                       value="<?= htmlspecialchars((string) ($campos['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="botones-app">
                <button type="submit" class="btn-sm primario">Guardar</button>
                <?php if ($id > 0): ?>
                <a class="btn-sm" href="index.php?controlador=Producto&accion=categorias">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
