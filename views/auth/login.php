<?php
/**
 * VISTA: Formulario de login
 * Sistema de Ventas - Farmacia
 * Variables: $error (string|null), $expiro (bool)
 * Campos: usuario, DNI, contrasena, tipo de usuario y
 *         recuadro "Recordar mi sesion por 14 dias".
 * Incluye token anti-CSRF (campo oculto).
 * Estilos: css/styles.css (hoja de estilos externa)
 */
$usuarioAnterior = (string) ($_POST['usuario'] ?? '');
$dniAnterior     = (string) ($_POST['dni'] ?? '');
$tipoAnterior    = (string) ($_POST['tipo_usuario'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Ventas</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="tarjeta">
        <div class="marca">
            <div class="logo">💊</div>
            <h1>Sistema de Ventas</h1>
            <p>Farmacia · Acceso del personal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($expiro)): ?>
            <div class="info">
                Su sesión terminó por inactividad. Ingrese de nuevo para continuar.
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?controlador=Auth&accion=procesar">
            <!-- Token anti-CSRF de la sesion (obligatorio en todo form) -->
            <input type="hidden" name="csrf"
                   value="<?= htmlspecialchars(UsuarioController::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario"
                       value="<?= htmlspecialchars($usuarioAnterior, ENT_QUOTES, 'UTF-8') ?>"
                       required autofocus autocomplete="username">
            </div>

            <div class="campo">
                <label for="dni">DNI</label>
                <input type="text" id="dni" name="dni"
                       value="<?= htmlspecialchars($dniAnterior, ENT_QUOTES, 'UTF-8') ?>"
                       inputmode="numeric" pattern="[0-9]{8}" maxlength="8"
                       data-digitos="8" data-sin-contador
                       placeholder="12345678" required autocomplete="off">
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <div class="campo">
                <label for="tipo_usuario">Tipo de usuario</label>
                <select id="tipo_usuario" name="tipo_usuario" required>
                    <option value="" <?= $tipoAnterior === '' ? 'selected' : '' ?> disabled>
                        Seleccione su tipo de usuario…
                    </option>
                    <option value="Vendedor" <?= $tipoAnterior === 'Vendedor' ? 'selected' : '' ?>>
                        Vendedor
                    </option>
                    <option value="Administrador" <?= $tipoAnterior === 'Administrador' ? 'selected' : '' ?>>
                        Administrador
                    </option>
                    <option value="Almacenero" <?= $tipoAnterior === 'Almacenero' ? 'selected' : '' ?>>
                        Almacenero
                    </option>
                </select>
            </div>

            <div class="campo">
                <label class="check" for="recordar">
                    <input type="checkbox" id="recordar" name="recordar" value="1"
                           <?= !empty($_POST['recordar']) ? 'checked' : '' ?>>
                    <span>Recordar mi sesión por 14 días</span>
                </label>
            </div>

            <button type="submit" class="btn-ingresar">Ingresar</button>
        </form>

        <p class="version">Sistema de Ventas · Farmacia — v<?= APP_VERSION ?></p>
    </div>
    <!-- Validacion en vivo de campos (DNI: 8 digitos) sin recargar -->
    <script src="js/app/validacion-forms.js" defer></script>
</body>
</html>
