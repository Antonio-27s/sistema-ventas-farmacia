<?php
/**
 * VISTA: Gestión de usuarios (listado) — solo Administrador
 * Sistema de Ventas - Farmacia
 * Variables: $usuarios (filas con rol), $ultimos (mapa auditoria),
 *            $idActual (mi id), $msg (aviso tras POST),
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$textosMsg = [
    'usuario-ok' => 'Usuario guardado correctamente.',
    'estado-ok'  => 'Estado del usuario actualizado.',
    'pass-ok'    => 'Contraseña actualizada correctamente.',
    'error'      => 'No se pudo completar la acción.',
];
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app">
    <div class="fila-titulo">
        <div>
            <h1>Gestión de usuarios</h1>
            <p class="sub">
                Crear, editar, activar/desactivar cuentas y definir
                la contraseña (solo Administrador).
            </p>
        </div>
        <div class="botones-app sin-margen">
            <a class="btn-sm primario" href="index.php?controlador=Usuarios&accion=nuevo">+ Nuevo usuario</a>
        </div>
    </div>

    <?php if ($msg !== ''): ?>
    <div class="aviso<?= $msg === 'error' ? '' : ' ok' ?>">
        <?= htmlspecialchars((string) ($textosMsg[$msg] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="tabla-scroll">
        <table class="tabla">
            <tr>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Rol</th>
                <th>Último acceso</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($usuarios as $u): ?>
            <?php $esYo = ((int) $u['id_usuario'] === $idActual); ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars((string) $u['usuario'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($esYo): ?><span class="badge off">tú</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(
                    trim($u['nombre'] . ' ' . $u['apellido_paterno'] . ' ' . $u['apellido_materno']),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></td>
                <td><?= htmlspecialchars((string) $u['dni'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $u['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(
                    (string) ($ultimos[(string) $u['usuario']] ?? '—'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></td>
                <td>
                    <?php if ((int) $u['activo'] === 1): ?>
                    <span class="badge ok">Activo</span>
                    <?php else: ?>
                    <span class="badge off">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td class="celda-acciones">
                    <a class="btn-sm"
                       href="index.php?controlador=Usuarios&accion=editar&id=<?= (int) $u['id_usuario'] ?>">
                        Editar
                    </a>
                    <a class="btn-sm"
                       href="index.php?controlador=Usuarios&accion=password&usuario_id=<?= (int) $u['id_usuario'] ?>">
                        Contraseña
                    </a>
                    <?php if ($esYo): ?>
                    <span class="nota-linea">no puedes desactivarte a ti mismo</span>
                    <?php else: ?>
                    <form class="forma-linea"
                          method="post"
                          action="index.php?controlador=Usuarios&accion=toggle_estado">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="usuario_id" value="<?= (int) $u['id_usuario'] ?>">
                        <button type="submit" class="btn-sm <?= (int) $u['activo'] === 1 ? 'peligro' : '' ?>">
                            <?= (int) $u['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="aviso">
        La contraseña la define el <strong>Administrador
        directamente</strong> (mínimo 8 caracteres): la inicial al
        crear la cuenta y luego por «Definir contraseña»; el usuario
        no gestiona la suya: no hay temporales ni auto-servicio.
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
