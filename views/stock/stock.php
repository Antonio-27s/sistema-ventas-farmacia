<?php
/**
 * VISTA: Stock (modulo Stock, accion default)
 * Sistema de Ventas - Farmacia
 *
 * El HTML se renderiza en el servidor (funciona sin JS); las
 * dinamicas (busqueda/filtro y ajuste) las hace
 * public/js/stock/stock.js (progressive enhancement).
 *
 * Variables: $filas, $q, $estado, $msg,
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$textosMsg = [
    'ajuste-ok' => 'Stock actualizado.',
    'error'     => 'No se pudo completar la acción.',
];
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Stock</h1>
            <p class="sub">
                <span id="stock-cantidad"><?= count($filas) ?> producto(s)</span>
                — cantidad actual vs. mínimo por producto
            </p>
        </div>
        <div class="botones-app sin-margen">
            <a class="btn-sm" href="index.php?controlador=Producto">Catálogo</a>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
    <div class="aviso<?= $msg === 'error' ? '' : ' ok' ?>">
        <?= htmlspecialchars((string) ($textosMsg[$msg] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <!-- Sin JS: GET real que recarga la pagina con el filtro -->
    <form class="barra-busqueda" id="stock-busqueda" method="get" action="index.php">
        <input type="hidden" name="controlador" value="Stock">
        <input type="text" id="stock-q" name="q"
               value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Buscar por nombre o código…">
        <select id="stock-estado" name="estado">
            <option value="">Todos los estados</option>
            <option value="BAJO" <?= $estado === 'BAJO' ? 'selected' : '' ?>>Bajo</option>
            <option value="AGOTADO" <?= $estado === 'AGOTADO' ? 'selected' : '' ?>>Agotado</option>
        </select>
        <button type="submit" class="btn-sm">Filtrar</button>
    </form>

    <div class="tabla-scroll">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Mínimo</th>
                    <th>Estado</th>
                    <th>Últ. actualización</th>
                    <th>Ajuste</th>
                </tr>
            </thead>
            <tbody id="stock-cuerpo">
                <?php if ($filas === []): ?>
                <tr>
                    <td colspan="8" class="vacio">No hay productos que coincidan.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($filas as $s): ?>
                <?php
                $estadoFila = Stock::estadoDe((int) $s['cantidad'], (int) $s['stock_minimo']);
                $claseBadge = ['OK' => 'ok', 'BAJO' => 'warn', 'AGOTADO' => 'off'];
                ?>
                <tr>
                    <td><?= htmlspecialchars((string) $s['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars((string) $s['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars((string) $s['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="celda-cantidad"><strong><?= (int) $s['cantidad'] ?></strong></td>
                    <td><?= (int) $s['stock_minimo'] ?></td>
                    <td class="celda-estado">
                        <span class="badge <?= $claseBadge[$estadoFila] ?>"><?= Stock::ESTADOS[$estadoFila] ?></span>
                    </td>
                    <td class="celda-fecha">
                        <?= $s['fecha_actualizacion'] !== null
                            ? date('d/m/Y H:i', strtotime((string) $s['fecha_actualizacion']))
                            : '—' ?>
                    </td>
                    <td class="celda-acciones">
                        <form class="forma-linea" data-ajusta="1" method="post"
                              action="index.php?controlador=Stock&accion=ajustar">
                            <input type="hidden" name="csrf"
                                   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_producto" value="<?= (int) $s['id_producto'] ?>">
                            <input type="number" name="cantidad" class="ajusta-cant"
                                   min="0" step="1" value="<?= (int) $s['cantidad'] ?>">
                            <button type="submit" class="btn-sm">Actualizar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/stock/stock.js" defer></script>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
