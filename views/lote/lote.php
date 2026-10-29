<?php
/**
 * VISTA: Lotes (modulo Lote, accion default) — listado FEFO
 * Sistema de Ventas - Farmacia
 *
 * El HTML se renderiza en el servidor (funciona sin JS); la
 * busqueda la hace public/js/lote/lote.js (progressive
 * enhancement).
 *
 * Variables: $filas, $q, $msg,
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$textosMsg = [
    'lote-ok' => 'Lote registrado; su cantidad entró al stock del producto.',
    'error'   => 'No se pudo completar la acción.',
];
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Lotes</h1>
            <p class="sub">
                <span id="lote-cantidad"><?= count($filas) ?> lote(s)</span>
                — salida FEFO: primero vence, primero sale
            </p>
        </div>
        <div class="botones-app sin-margen">
            <a class="btn-sm primario" href="index.php?controlador=Lote&accion=nuevo">+ Nuevo lote</a>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
    <div class="aviso<?= $msg === 'error' ? '' : ' ok' ?>">
        <?= htmlspecialchars((string) ($textosMsg[$msg] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <form class="barra-busqueda" id="lote-busqueda" method="get" action="index.php">
        <input type="hidden" name="controlador" value="Lote">
        <input type="text" id="lote-q" name="q"
               value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Buscar por lote, producto o código…">
        <button type="submit" class="btn-sm">Filtrar</button>
    </form>

    <div class="tabla-scroll">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Número de lote</th>
                    <th>Cantidad</th>
                    <th>F. fabricación</th>
                    <th>F. vencimiento</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="lote-cuerpo">
                <?php if ($filas === []): ?>
                <tr>
                    <td colspan="6" class="vacio">
                        No hay lotes registrados.
                        <a href="index.php?controlador=Lote&accion=nuevo">Registrar el primero</a>.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($filas as $l): ?>
                <?php
                $estadoLote = Lote::estadoVencimiento((string) $l['fecha_vencimiento']);
                $claseBadge = ['VENCIDO' => 'off', 'PRONTO' => 'warn', 'VIGENTE' => 'ok'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars(
                            (string) $l['producto'],
                            ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="sub-celda"><?= htmlspecialchars(
                            (string) $l['codigo_producto'],
                            ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><?= htmlspecialchars((string) $l['numero_lote'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $l['cantidad'] ?></td>
                    <td><?= $l['fecha_fabricacion'] !== null
                        ? date('d/m/Y', strtotime((string) $l['fecha_fabricacion']))
                        : '—' ?></td>
                    <td><strong><?= date('d/m/Y', strtotime((string) $l['fecha_vencimiento'])) ?></strong></td>
                    <td>
                        <span class="badge <?= $claseBadge[$estadoLote] ?>"><?= Lote::ESTADOS[$estadoLote] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/lote/lote.js" defer></script>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
