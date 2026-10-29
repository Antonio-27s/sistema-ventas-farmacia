<?php
/**
 * VISTA: Mi perfil ("Ver perfil" del header)
 * Sistema de Ventas - Farmacia
 * Datos del usuario logueado, leidos de la BD + ultimo acceso
 * exitoso segun la auditoria (intentos_login).
 * Variables: $usuario (BD), $rolNombre, $ultimos (mapa),
 *            $modulosAside, $moduloActual, $panelSesion, $titulo
 */
$nombreCompleto = trim(
    $usuario['nombre'] . ' '
    . $usuario['apellido_paterno'] . ' '
    . $usuario['apellido_materno']
);
$ultimo = $ultimos[(string) $_SESSION['usuario']['usuario']] ?? '—';
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <h1>Mi perfil</h1>
    <p class="sub">Tus datos, tal como están en la base de datos.</p>

    <table class="tabla">
        <tr>
            <th scope="row">Usuario (login)</th>
            <td><?= htmlspecialchars((string) $usuario['usuario'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th scope="row">Nombre completo</th>
            <td><?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th scope="row">DNI</th>
            <td><?= htmlspecialchars((string) $usuario['dni'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th scope="row">Rol</th>
            <td><?= htmlspecialchars((string) ($rolNombre ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th scope="row">Estado</th>
            <td>
                <?php if ((int) $usuario['activo'] === 1): ?>
                <span class="badge ok">Activo</span>
                <?php else: ?>
                <span class="badge off">Inactivo</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Último acceso exitoso</th>
            <td><?= htmlspecialchars($ultimo, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>

    <div class="aviso">
        Tu contraseña y el estado de tu cuenta los gestiona el
        <strong>Administrador</strong> (módulo Usuarios): no existe
        auto-servicio de contraseñas en el sistema.
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
