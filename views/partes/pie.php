<?php
/**
 * PARTE: pie de la aplicacion (shell)
 * Sistema de Ventas - Farmacia
 * Cierra el main, renderiza el panel de sesion (temporal),
 * su fondo y el JavaScript de abrir/cerrar.
 * Requiere: $usuario (array|null) y $rolNombre (de la vista).
 */
$datosSesion    = $_SESSION['usuario'] ?? [];
$cookieOk       = isset($_COOKIE[session_name()]);
$recordadoHasta = (int) ($datosSesion['recordado_hasta'] ?? 0);
?>
        </main>
    </div>

    <div id="panel-fondo"<?= $panelSesion ? ' class="visible"' : '' ?>></div>

    <aside id="panel-sesion"<?= $panelSesion ? ' class="abierto"' : '' ?>>
        <button type="button" class="cerrar-panel" id="panel-cerrar" title="Cerrar panel">✕</button>
        <h2>Sesión de autenticación</h2>
        <div class="ok">✔ SESIÓN ACTIVA VERIFICADA</div>

        <table>
            <tr>
                <td>Estado de la sesión</td>
                <td><?= session_status() === PHP_SESSION_ACTIVE ? 'ACTIVA' : 'INACTIVA' ?></td>
            </tr>
            <tr>
                <td>Nombre de la cookie</td>
                <td><?= htmlspecialchars(session_name(), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Cookie recibida en la petición</td>
                <td><?= $cookieOk ? 'SÍ' : 'NO' ?></td>
            </tr>
            <tr>
                <td>ID de sesión</td>
                <td><?= htmlspecialchars(session_id(), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Recordar sesión (14 días)</td>
                <td><?= $recordadoHasta > 0
                    ? 'Sí — hasta ' . date('d/m/Y H:i', $recordadoHasta)
                    : 'No (solo mientras el navegador esté abierto)' ?></td>
            </tr>
            <tr>
                <td>Usuario (login)</td>
                <td><?= htmlspecialchars((string) ($datosSesion['usuario'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>DNI</td>
                <td><?= htmlspecialchars((string) ($datosSesion['dni'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Nombre completo</td>
                <td><?= htmlspecialchars(
                    (string) ($usuario['nombre'] ?? '-') . ' '
                    . (string) ($usuario['apellido_paterno'] ?? '') . ' '
                    . (string) ($usuario['apellido_materno'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></td>
            </tr>
            <tr>
                <td>Rol</td>
                <td><?= htmlspecialchars(
                    (string) ($rolNombre ?? ('ID ' . (int) ($usuario['id_rol'] ?? 0))),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></td>
            </tr>
            <tr>
                <td>Hora del servidor</td>
                <td><?= date('d/m/Y H:i:s') ?></td>
            </tr>
        </table>

        <div class="version">Sistema de Ventas · Farmacia — v<?= APP_VERSION ?></div>
    </aside>

    <!-- JS del shell (cada vista de modulo carga ademas el JS
         propio: public/js/{modulo}/... ) -->
    <script src="js/app/panel-sesion.js" defer></script>
</body>
</html>
