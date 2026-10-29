<?php
/**
 * VISTA: Modulo en construccion (dentro del shell)
 * Sistema de Ventas - Farmacia
 * El modulo ya esta reservado para el rol (matriz RBAC),
 * pero su implementacion llegara en un proximo incremento.
 * Variables: $modulo (string), $rolNombre (string) + variables shell
 */
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="marca-center">
        <div class="logo">🚧</div>
        <h1>Módulo en construcción</h1>
        <p>El acceso está habilitado, pero aún no se implementó</p>
    </div>

    <div class="aviso">
        El módulo
        <strong><?= htmlspecialchars((string) $modulo, ENT_QUOTES, 'UTF-8') ?></strong>
        ya está reservado para tu rol
        (<strong><?= htmlspecialchars((string) $rolNombre, ENT_QUOTES, 'UTF-8') ?></strong>);
        su implementación llegará en un próximo incremento del sistema.
    </div>

    <div class="botones-app">
        <a class="btn-sm" href="index.php?controlador=Inicio">Ir al inicio</a>
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
