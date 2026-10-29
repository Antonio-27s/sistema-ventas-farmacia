<?php
/**
 * VISTA: Definir contraseña (módulo Usuarios) — solo Administrador
 * Sistema de Ventas - Farmacia
 * Variables: $destino (fila del usuario objetivo),
 *            $error (string|null: mensaje de validacion),
 *            $usuario, $rolNombre, $modulosAside, $moduloActual,
 *            $panelSesion, $titulo
 */
$nombreDestino = trim(
    $destino['nombre'] . ' '
    . $destino['apellido_paterno'] . ' '
    . $destino['apellido_materno']
);
?>
<?php require BASE_PATH . '/views/partes/cabecera.php'; ?>
<div class="tarjeta-app narrow">
    <h1>Definir contraseña</h1>
    <p class="sub">
        Nueva contraseña para
        <strong><?= htmlspecialchars((string) $destino['usuario'], ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars($nombreDestino, ENT_QUOTES, 'UTF-8') ?>,
        <?= htmlspecialchars((string) $destino['rol'], ENT_QUOTES, 'UTF-8') ?>).
    </p>

    <?php if ($error !== null): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="form-app"
          method="post"
          action="index.php?controlador=Usuarios&accion=guardar_password">
        <input type="hidden" name="csrf"
               value="<?= htmlspecialchars(UsuarioController::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="usuario_id" value="<?= (int) $destino['id_usuario'] ?>">

        <div class="campo">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password"
                   minlength="8" required autocomplete="new-password">
            <p class="ayuda">Mínimo 8 caracteres.</p>
        </div>

        <div class="campo">
            <label for="password2">Confirmar contraseña</label>
            <input type="password" id="password2" name="password2"
                   minlength="8" required autocomplete="new-password">
        </div>

        <div class="botones-app">
            <button type="submit" class="btn-sm">Guardar contraseña</button>
            <a class="btn-sm" href="index.php?controlador=Usuarios&accion=mostrar">Volver al listado</a>
        </div>
    </form>

    <div class="aviso">
        Al guardar, el usuario podrá entrar con esta contraseña en su
        próximo login. El hash se genera con el algoritmo del sistema
        (Argon2id, respaldo bcrypt).
    </div>
</div>
<?php require BASE_PATH . '/views/partes/pie.php'; ?>
