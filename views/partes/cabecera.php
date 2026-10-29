<?php
/**
 * PARTE: header de la aplicacion (shell)
 * Sistema de Ventas - Farmacia
 * Lo usan TODAS las vistas autenticadas (el login no).
 *
 * Variables esperadas (con valores por defecto):
 *   $usuario      (array|null: fila reconstruida desde la BD)
 *   $rolNombre    (string|null: rol desde la BD)
 *   $modulosAside (array: modulos para el aside, segun RBAC)
 *   $moduloActual (string: modulo resaltado en el aside)
 *   $panelSesion  (bool: abrir el panel de sesion al cargar)
 *   $titulo       (string: titulo del documento)
 */
$usuario      = $usuario ?? null;
$rolNombre    = $rolNombre ?? null;
$modulosAside = $modulosAside ?? [];
$moduloActual = $moduloActual ?? '';
$panelSesion  = $panelSesion ?? false;
$titulo       = $titulo ?? 'Sistema de Ventas';

$nombreCompleto = $usuario !== null
    ? trim(
        $usuario['nombre'] . ' '
        . $usuario['apellido_paterno'] . ' '
        . $usuario['apellido_materno']
    )
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Token CSRF para los fetch del JS (mismo que los forms) -->
    <meta name="csrf-token" content="<?= htmlspecialchars(UsuarioController::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> - Sistema de Ventas</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- JS del shell: ayuda de fetch (API) y validacion de
         formularios sin recargar (cada vista de modulo carga
         ademas su propio JS: public/js/{modulo}/...) -->
    <script src="js/app/api.js" defer></script>
    <script src="js/app/validacion-forms.js" defer></script>
</head>
<body class="app">
    <header class="topbar">
        <div class="marca-top">
            <span class="logo-mini">💊</span>
            <span class="marca-nombre">Sistema de Ventas<small>Farmacia</small></span>
        </div>

        <div class="chip-usuario" title="Usuario logueado">
            <strong><?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars((string) ($rolNombre ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <nav>
            <a href="index.php?controlador=Inicio"<?= $moduloActual === 'Inicio' ? ' class="activa"' : '' ?>>Inicio</a>
            <a href="index.php?controlador=Usuario"
               <?= $moduloActual === 'Usuario' ? ' class="activa"' : '' ?>>Ver perfil</a>
            <button type="button" class="js-abre-sesion"
                    title="Datos de la sesión (vista temporal)">Ver sesión</button>
            <a class="salir" href="index.php?controlador=Usuario&accion=cerrar">Cerrar sesión</a>
        </nav>
    </header>

    <div class="layout">
        <aside class="aside">
            <h2>Módulos</h2>
            <ul>
                <?php foreach ($modulosAside as $mod): ?>
                <li>
                    <a href="index.php?controlador=<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>"
                       <?= $moduloActual === $mod ? 'class="activa"' : '' ?>>
                        <?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="principal">
