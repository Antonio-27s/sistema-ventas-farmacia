<?php
/**
 * VISTA: Catalogo de productos (modulo Producto, accion default)
 * Sistema de Ventas - Farmacia
 *
 * El HTML se renderiza en el servidor (funciona sin JS); las
 * dinamicas (busqueda/filtro y activar/inactivar) las hace
 * public/js/producto/catalogo.js (progressive enhancement).
 *
 * Variables: $productos, $categorias, $q, $idCat, $msg,
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$textosMsg = [
    'producto-ok' => 'Producto guardado correctamente.',
    'estado-ok'   => 'Estado del producto actualizado.',
    'error'       => 'No se pudo completar la acción.',
];
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Catálogo de productos</h1>
            <p class="sub">
                <span id="catalogo-cantidad"><?= count($productos) ?> producto(s)</span>
                — búsqueda por nombre, código o principio activo
            </p>
        </div>
        <div class="botones-app sin-margen">
            <a class="btn-sm" href="index.php?controlador=Producto&accion=categorias">Categorías</a>
            <a class="btn-sm" href="index.php?controlador=Producto&accion=laboratorios">Laboratorios</a>
            <a class="btn-sm primario" href="index.php?controlador=Producto&accion=nuevo">+ Nuevo producto</a>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
    <div class="aviso<?= $msg === 'error' ? '' : ' ok' ?>">
        <?= htmlspecialchars((string) ($textosMsg[$msg] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <!-- Sin JS: GET real que recarga la pagina con el filtro -->
    <form class="barra-busqueda" id="catalogo-busqueda" method="get" action="index.php">
        <input type="hidden" name="controlador" value="Producto">
        <input type="text" id="catalogo-q" name="q"
               value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Buscar por nombre, código o principio activo…">
        <select id="catalogo-categoria" name="id_categoria">
            <option value="0">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= (int) $c['id_categoria'] ?>"
                <?= $idCat === (int) $c['id_categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars((string) $c['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-sm">Filtrar</button>
    </form>

    <div class="tabla-scroll">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Laboratorio</th>
                    <th>Tipo</th>
                    <th>Precio venta</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="catalogo-cuerpo">
                <?php if ($productos === []): ?>
                <tr>
                    <td colspan="9" class="vacio">
                        No hay productos que coincidan.
                        <a href="index.php?controlador=Producto&accion=nuevo">Crear el primero</a>.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $p['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <strong><?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ((string) $p['principio_activo'] !== ''): ?>
                        <span class="sub-celda"><?= htmlspecialchars(
                            (string) $p['principio_activo'], ENT_QUOTES, 'UTF-8'
                        ) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) $p['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($p['laboratorio'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars(
                            (string) (Producto::TIPOS_LABEL[(string) $p['tipo_producto']]
                                ?? (string) $p['tipo_producto']),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                        <?php if ((int) $p['requiere_receta'] === 1): ?>
                        <span class="badge off">receta</span>
                        <?php endif; ?>
                    </td>
                    <td>S/ <?= number_format((float) $p['precio_venta'], 2) ?></td>
                    <td>
                        <?php if ((int) $p['stock'] === 0): ?>
                        <span class="badge off">0</span>
                        <?php else: ?>
                        <strong><?= (int) $p['stock'] ?></strong>
                        <?php endif; ?>
                    </td>
                    <td class="celda-estado">
                        <?php if ((int) $p['activo'] === 1): ?>
                        <span class="badge ok">Activo</span>
                        <?php else: ?>
                        <span class="badge off">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="celda-acciones">
                        <a class="btn-sm"
                           href="index.php?controlador=Producto&accion=editar&id=<?= (int) $p['id_producto'] ?>">
                            Editar
                        </a>
                        <form class="forma-linea" data-togla="1" method="post"
                              action="index.php?controlador=Producto&accion=toggle_estado">
                            <input type="hidden" name="csrf"
                                   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="producto_id" value="<?= (int) $p['id_producto'] ?>">
                            <button type="submit"
                                    class="btn-sm <?= (int) $p['activo'] === 1 ? 'peligro' : '' ?>">
                                <?= (int) $p['activo'] === 1 ? 'Inactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/producto/catalogo.js" defer></script>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
