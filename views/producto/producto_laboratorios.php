<?php
/**
 * VISTA: Laboratorios (modulo Producto)
 * Sistema de Ventas - Farmacia
 * Variables: $lista, $id (0 = crear; >0 = editar), $campos
 *            (datos a re-llenar), $error (string|null),
 *            $msg (string) + variables shell
 */
$textosMsg = [
    'lab-ok' => 'Laboratorio guardado correctamente.',
    'error'  => 'No se pudo completar la acción.',
];
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Laboratorios</h1>
            <p class="sub">Fabricas/empresas que producen los productos.</p>
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
                    <th>RUC</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                <?php if ($lista === []): ?>
                <tr>
                    <td colspan="6" class="vacio">
                        Aún no hay laboratorios. Cree el primero en el formulario.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($lista as $l): ?>
                <tr>
                    <td><strong><?= htmlspecialchars((string) $l['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars((string) ($l['ruc'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($l['telefono'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($l['correo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ((int) $l['activo'] === 1): ?>
                        <span class="badge ok">Activo</span>
                        <?php else: ?>
                        <span class="badge off">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="celda-acciones">
                        <a class="btn-sm"
                        href="index.php?controlador=Producto&accion=laboratorios&id=<?= (int) $l['id_laboratorio'] ?>">
                            Editar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <form class="form-app" method="post"
              action="index.php?controlador=Producto&accion=guardar_laboratorio">
            <h3 class="form-titulo"><?= $id > 0 ? 'Editar laboratorio' : 'Nuevo laboratorio' ?></h3>

            <?php if ($error !== null): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_laboratorio" value="<?= $id ?>">

            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="150"
                       value="<?= htmlspecialchars((string) ($campos['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="ruc">RUC</label>
                <input type="text" id="ruc" name="ruc" maxlength="11" data-digitos="11"
                       value="<?= htmlspecialchars((string) ($campos['ruc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <p class="ayuda">11 dígitos, opcional.</p>
            </div>
            <div class="campo">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" maxlength="20"
                       value="<?= htmlspecialchars((string) ($campos['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" maxlength="200"
                       value="<?= htmlspecialchars((string) ($campos['direccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" maxlength="150"
                       value="<?= htmlspecialchars((string) ($campos['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <div class="check-claro">
                    <input type="checkbox" id="activo" name="activo"
                        <?= (int) ($campos['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <label for="activo">Activo (disponible en el catálogo)</label>
                </div>
            </div>

            <div class="botones-app">
                <button type="submit" class="btn-sm primario">Guardar</button>
                <?php if ($id > 0): ?>
                <a class="btn-sm" href="index.php?controlador=Producto&accion=laboratorios">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
