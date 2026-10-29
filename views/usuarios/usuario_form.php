<?php
/**
 * VISTA: Formulario de usuario (nuevo o edicion) — solo Administrador
 * Sistema de Ventas - Farmacia
 *
 * La BD es la fuente de las longitudes: maxlength = longitud de
 * columna; la validacion en vivo (contador n/max y DNI de 8
 * digitos sin recargar) la hace public/js/app/validacion-forms.js.
 * En edicion el rol y "activo" se bloquean si es la cuenta propia
 * (anti lockout); los campos deshabilitados no viajan en el POST,
 * por eso se envia su valor en un hidden aparte.
 *
 * Variables: $destino (array|null: usuario existente; null = nuevo),
 *            $campos (array: valores a re-llenar), $errores (string[]),
 *            $roles (filas de rol), $esEdicion (bool), $esYo (bool),
 *            $usuario, $rolNombre, $modulosAside,
 *            $moduloActual, $panelSesion, $titulo
 * Nota: $destino (no $usuario) para el usuario objetivo: la shell
 * (contextoVista) define $usuario = la cuenta logueada.
 */
$csrf = UsuarioController::csrfToken();
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app wide">
    <h1><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
    <p class="sub">
        <?php if ($esEdicion): ?>
        Datos de la cuenta «<?= htmlspecialchars(
            (string) ($destino['usuario'] ?? ''),
            ENT_QUOTES, 'UTF-8') ?>».
        <?php else: ?>
        Complete los datos de la nueva cuenta.
        <?php endif; ?>
    </p>

    <?php if ($errores !== []): ?>
    <div class="error">
        <?php foreach ($errores as $e): ?>
        <div>• <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form class="form-app" method="post"
          action="index.php?controlador=Usuarios&accion=guardar">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_usuario"
               value="<?= $esEdicion ? (int) ($destino['id_usuario'] ?? 0) : 0 ?>">

        <div class="grid-2">
            <div class="campo">
                <label for="usuario">Usuario (login) *</label>
                <input type="text" id="usuario" name="usuario" required maxlength="50"
                       autocomplete="off"
                       value="<?= htmlspecialchars((string) ($campos['usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="dni">DNI *</label>
                <input type="text" id="dni" name="dni" required maxlength="8"
                       inputmode="numeric" pattern="[0-9]{8}"
                       data-digitos="8" data-sin-contador autocomplete="off"
                       value="<?= htmlspecialchars((string) ($campos['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100"
                       value="<?= htmlspecialchars((string) ($campos['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="apellido_paterno">Apellido paterno *</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" required maxlength="100"
                       value="<?= htmlspecialchars(
                           (string) ($campos['apellido_paterno'] ?? ''),
                           ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="apellido_materno">Apellido materno *</label>
                <input type="text" id="apellido_materno" name="apellido_materno" required maxlength="100"
                       value="<?= htmlspecialchars(
                           (string) ($campos['apellido_materno'] ?? ''),
                           ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="campo">
                <label for="id_rol">Rol *</label>
                <?php if ($esYo): ?>
                <input type="hidden" name="id_rol" value="<?= (int) ($campos['id_rol'] ?? 0) ?>">
                <select id="id_rol" disabled>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id_rol'] ?>"
                        <?= (int) ($campos['id_rol'] ?? 0) === (int) $r['id_rol'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $r['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="ayuda">No puedes cambiar el rol de tu propia cuenta.</p>
                <?php else: ?>
                <select id="id_rol" name="id_rol" required>
                    <option value="0">— seleccione —</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id_rol'] ?>"
                        <?= (int) ($campos['id_rol'] ?? 0) === (int) $r['id_rol'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $r['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($esEdicion): ?>
        <div class="aviso">
            Para cambiar la contraseña de esta cuenta, use
            <a href="index.php?controlador=Usuarios&accion=password&usuario_id=<?= (int) (
                $destino['id_usuario'] ?? 0) ?>">
                Definir contraseña
            </a>
            en el listado (la edicion de perfil no toca la contraseña).
        </div>
        <?php else: ?>
        <div class="grid-2">
            <div class="campo">
                <label for="password">Contraseña inicial *</label>
                <input type="password" id="password" name="password"
                       required minlength="8" autocomplete="new-password">
                <p class="ayuda">Mínimo 8 caracteres. El Administrador podrá cambiarla luego desde el listado.</p>
            </div>
            <div class="campo">
                <label for="password2">Confirmar contraseña *</label>
                <input type="password" id="password2" name="password2"
                       required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <?php endif; ?>

        <div class="campo">
            <div class="check-claro">
                <?php if ($esYo): ?>
                <input type="hidden" name="activo" value="1">
                <input type="checkbox" id="activo" disabled checked>
                <?php else: ?>
                <input type="checkbox" id="activo" name="activo" value="1"
                    <?= (int) ($campos['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                <?php endif; ?>
                <label for="activo">Cuenta activa</label>
            </div>
            <?php if ($esYo): ?>
            <p class="ayuda">No puedes desactivar tu propia cuenta.</p>
            <?php endif; ?>
        </div>

        <div class="botones-app">
            <button type="submit" class="btn-sm primario">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
            <a class="btn-sm" href="index.php?controlador=Usuarios&accion=mostrar">Volver al listado</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
