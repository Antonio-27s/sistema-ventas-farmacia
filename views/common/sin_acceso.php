<?php
/**
 * VISTA: Sin acceso (el rol no tiene este modulo) — dentro del shell
 * Sistema de Ventas - Farmacia
 * Variables: $modulo (string), $rolNombre (string) + variables shell
 */
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="marca-center">
        <div class="logo">🔒</div>
        <h1>Sin acceso</h1>
        <p>Su rol no tiene permiso para este módulo</p>
    </div>

    <div class="aviso">
        El rol
        <strong><?= htmlspecialchars((string) $rolNombre, ENT_QUOTES, 'UTF-8') ?></strong>
        no cuenta con acceso al módulo
        <strong><?= htmlspecialchars((string) $modulo, ENT_QUOTES, 'UTF-8') ?></strong>.
        Si considera que es un error, contacte al administrador del sistema.
    </div>

    <div class="botones-app">
        <a class="btn-sm" href="index.php?controlador=Inicio">Ir al inicio</a>
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
