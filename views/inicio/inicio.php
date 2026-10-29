<?php
/**
 * VISTA: Inicio (dashboard)
 * Sistema de Ventas - Farmacia
 * Home comun de todos los roles, dentro del shell.
 * Variables: $usuario (BD), $rolNombre, $modulosAside,
 *            $moduloActual, $panelSesion, $titulo
 */
$descripcionesRol = [
    'Administrador' => 'Gestión completa del sistema: usuarios, inventario, compras, ventas, caja y reportes.',
    'Vendedor'      => 'Atención al cliente: ventas, clientes, recetas y caja.',
    'Almacenero'    => 'Gestión de inventario: compras, productos, stock y lotes.',
];
$miRol        = (string) $rolNombre;
$modulosRol   = Rol::MODULOS_POR_ROL[$miRol] ?? [];
$modulosHechos = ['Inicio', 'Usuario', 'Usuarios', 'Producto', 'Stock', 'Lote'];
$recordadoHasta = (int) ($_SESSION['usuario']['recordado_hasta'] ?? 0);
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <h1>Hola, <?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?> 👋</h1>
    <p class="sub">
        Rol: <strong><?= htmlspecialchars($miRol, ENT_QUOTES, 'UTF-8') ?></strong>
        — <?= date('d/m/Y') ?>
    </p>

    <div class="tarjetas-3">
        <div class="mini-card">
            <h3>Tu rol</h3>
            <p class="texto-card"><?= htmlspecialchars(
                (string) ($descripcionesRol[$miRol] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            ) ?></p>
        </div>

        <div class="mini-card">
            <h3>Módulos de tu rol</h3>
            <ul class="lista-modulos">
                <?php foreach ($modulosRol as $mod): ?>
                <li>
                    <?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!in_array($mod, $modulosHechos, true)): ?>
                    <span class="badge off">próximamente</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="mini-card">
            <h3>Sesión</h3>
            <p class="texto-card">
                Activa
                — <?= $recordadoHasta > 0
                    ? 'recordada hasta ' . date('d/m/Y H:i', $recordadoHasta)
                    : 'válida mientras el navegador esté abierto' ?>.
                <br>Inactividad máxima: 10 min.
            </p>
            <button type="button" class="btn-sm js-abre-sesion">Ver datos de la sesión</button>
        </div>
    </div>
</div>
<div class="version-app">Sistema de Ventas · Farmacia — v<?= APP_VERSION ?></div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
