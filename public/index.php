<?php
/**
 * ============================================================
 * FRONT CONTROLLER - PUNTO DE ENTRADA UNICO
 * Sistema de Ventas - Farmacia (MVC PHP)
 * ============================================================
 * Toda peticion del navegador pasa por este archivo:
 *   1. cabeceras de seguridad
 *   2. carga la conexion a BD (config/)
 *   3. inicia la sesion activa (UsuarioController)
 *   4. reconstruye el usuario logueado DESDE LA BD (la sesion
 *      es una caché; la BD es la fuente de verdad)
 *   5. cierra la sesion si expiró la ventana de "recordar
 *      sesion" (14 dias) o el timeout de inactividad (10 min),
 *      y actualiza la ultima actividad
 *   6. enruta con la regla RBAC (matriz de modulos por rol)
 *      (dentro del try, junto a la verificacion del esquema:
 *       cualquier falla se pagina con detalle si APP_DEBUG)
 *
 * Rutas:
 *   index.php                                     → login (por defecto)
 *   index.php?controlador=Auth&accion=mostrar     → formulario de login
 *   index.php?controlador=Auth&accion=procesar    → valida credenciales (POST)
 *   index.php?controlador=Inicio                  → dashboard (shell, todos)
 *   index.php?controlador=Usuario                 → mi perfil ("Ver perfil")
 *   index.php?controlador=Usuario&accion=sesion   → shell con el panel de
 *                                                  sesion abierto (fallback)
 *   index.php?controlador=Usuario&accion=cerrar   → logout (sesion + cookie)
 *   index.php?controlador=Usuarios[&accion=...]   → gestion de usuarios
 *                                                  (solo Administrador)
 *   index.php?controlador=Producto[&accion=...]   → catalogo de productos
 *                                                  (Admin y Almacenero)
 *   index.php?controlador=Stock[&accion=...]      → stock por producto
 *                                                  (Admin y Almacenero)
 *   index.php?controlador=Lote[&accion=...]       → lotes FEFO + registro
 *                                                  (Admin y Almacenero)
 *   index.php?controlador=<Modulo>                → demas modulos (en construccion)
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// ------------------------------------------------------------
// CABECERAS DE SEGURIDAD (antes de cualquier salida)
// ------------------------------------------------------------
// Anti clickjacking: esta pagina NUNCA se renderiza en iframes
header("Content-Security-Policy: frame-ancestors 'none'");
// Anti MIME-sniffing: el navegador respeta el Content-Type exacto
header('X-Content-Type-Options: nosniff');
// No filtrar la URL (con tokens o IDs) a terceros
header('Referrer-Policy: same-origin');
// En produccion (HTTPS) descomentar:
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/config/esquema.php';
require BASE_PATH . '/lib/app.php';

// Carga automatica de clases (modelos y controladores)
spl_autoload_register(function (string $clase): void {
    foreach (['models', 'controllers'] as $dir) {
        $ruta = BASE_PATH . '/' . $dir . '/' . $clase . '.php';
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

// La sesion en el servidor debe vivir al menos lo que dura el
// "recordar sesion" mas largo (14 dias) + margen, o el archivo
// de sesion seria recolectado por el GC de PHP (por defecto
// ~24 min de inactividad) y el recordatorio no cumpliria.
ini_set('session.gc_maxlifetime', (string) (15 * 24 * 3600));

// 1) Iniciar (o reutilizar) la sesion en CADA peticion,
//    antes de cualquier header() de sesion o salida.
$usuarioControlador = new UsuarioController();
$usuarioControlador->iniciarSesion();

// 2) Reconstruir el usuario logueado desde la BD. Si ya no
//    existe, fue desactivado o su rol desaparecio: cerrar.
$usuarioActual = null;
$rolNombre     = null;

if (UsuarioController::estaAutenticado()) {
    $fila = (new Usuario())->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);

    if ($fila !== null && (int) $fila['activo'] === 1) {
        $usuarioActual = $fila;
        $rolNombre     = (new Rol())->nombrePorId((int) $fila['id_rol']);

        if ($rolNombre === null) {
            $usuarioControlador->cerrarSesion();
        }
    } else {
        $usuarioControlador->cerrarSesion();
    }
}

// 3) Vigencia de la sesion:
//    a) "Recordar sesion": si la ventana de 14 dias expiró,
//       cerrar (servidor + cookie).
//    b) Timeout de inactividad: 10 min sin peticiones = cerrar
//       (terminal desatendida). Vale tanto para sesiones
//       normales como recordadas.
if ($usuarioActual !== null) {
    if (UsuarioController::recordadoHasta() > 0
        && time() > UsuarioController::recordadoHasta()) {
        $usuarioControlador->cerrarSesion();
    }

    $ultima = (int) ($_SESSION['usuario']['ultima_actividad'] ?? 0);
    if ($ultima > 0
        && (time() - $ultima) > (UsuarioController::MINUTOS_INACTIVIDAD * 60)) {
        $usuarioControlador->cerrarSesion(true);
    }

    // Esta peticion cuenta como actividad
    $_SESSION['usuario']['ultima_actividad'] = time();
}

// 4) Enrutado (con verificacion previa del esquema)
$controlador = (string) ($_GET['controlador'] ?? 'Auth');
$accion      = (string) ($_GET['accion'] ?? 'mostrar');

try {
    // Verificar que el esquema tenga lo minimo (tablas y
    // columnas nuevas). Si falta una migracion, el catch
    // pagina la instruccion exacta (sin "Error interno"
    // generico ni fatal error).
    verificar_esquema();

    if ($controlador === 'Auth') {
        // El login no exige sesion.
        $auth = new AuthController();
        if ($accion === 'procesar') {
            $auth->procesar();
        } else {
            $auth->mostrar();
        }

    } elseif ($usuarioActual === null) {
        // Todo lo demas exige una sesion activa.
        header('Location: index.php?controlador=Auth&accion=mostrar');
        exit;

    } elseif (!Rol::puedeAcceder((string) $rolNombre, $controlador)) {
        // REGLA RBAC: el rol no tiene este modulo.
        $modulo       = $controlador;
        $usuario      = $usuarioActual;
        $modulosAside = Rol::modulosParaAside((string) $rolNombre);
        $moduloActual = '';
        $panelSesion  = false;
        $titulo       = 'Sin acceso';
        require BASE_PATH . '/views/common/sin_acceso.php';
        exit;

    } elseif ($controlador === 'Inicio') {
        // Dashboard comun (home de todos los roles).
        (new InicioController())->mostrar();

    } elseif ($controlador === 'Usuario') {
        // Mi perfil (default), sesion en panel (fallback) y cierre.
        if ($accion === 'cerrar') {
            $usuarioControlador->cerrarSesion();
        } elseif ($accion === 'sesion') {
            $usuarioControlador->mostrarSesion();
        } else {
            $usuarioControlador->mostrarPerfil();
        }

    } elseif ($controlador === 'Usuarios') {
        // Gestion de cuentas (solo Administrador; RBAC arriba).
        // Whitelist de acciones.
        $usuariosCtrl = new UsuariosController();
        if ($accion === 'toggle_estado') {
            $usuariosCtrl->toggleEstado();
        } elseif ($accion === 'password') {
            $usuariosCtrl->formPassword();
        } elseif ($accion === 'guardar_password') {
            $usuariosCtrl->guardarPassword();
        } elseif ($accion === 'nuevo') {
            $usuariosCtrl->formUsuario();
        } elseif ($accion === 'editar') {
            $usuariosCtrl->formUsuario();
        } elseif ($accion === 'guardar') {
            $usuariosCtrl->guardarUsuario();
        } else {
            $usuariosCtrl->listar();
        }

    } elseif ($controlador === 'Producto') {
        // Catalogo (Administrador y Almacenero; RBAC arriba).
        // Whitelist de acciones.
        $productoCtrl = new ProductoController();
        if ($accion === 'nuevo') {
            $productoCtrl->formProducto();
        } elseif ($accion === 'editar') {
            $productoCtrl->formProducto();
        } elseif ($accion === 'guardar') {
            $productoCtrl->guardarProducto();
        } elseif ($accion === 'toggle_estado') {
            $productoCtrl->toggleEstado();
        } elseif ($accion === 'categorias') {
            $productoCtrl->categorias();
        } elseif ($accion === 'guardar_categoria') {
            $productoCtrl->guardarCategoria();
        } elseif ($accion === 'laboratorios') {
            $productoCtrl->laboratorios();
        } elseif ($accion === 'guardar_laboratorio') {
            $productoCtrl->guardarLaboratorio();
        } else {
            $productoCtrl->listar();
        }

    } elseif ($controlador === 'Stock') {
        // Inventario (Administrador y Almacenero; RBAC arriba).
        $stockCtrl = new StockController();
        if ($accion === 'ajustar') {
            $stockCtrl->ajustar();
        } else {
            $stockCtrl->listar();
        }

    } elseif ($controlador === 'Lote') {
        // Lotes FEFO (Administrador y Almacenero; RBAC arriba).
        $loteCtrl = new LoteController();
        if ($accion === 'nuevo') {
            $loteCtrl->formLote();
        } elseif ($accion === 'guardar') {
            $loteCtrl->guardarLote();
        } else {
            $loteCtrl->listar();
        }

    } else {
        // Modulos reservados en la matriz pero no implementados.
        $modulo       = $controlador;
        $usuario      = $usuarioActual;
        $modulosAside = Rol::modulosParaAside((string) $rolNombre);
        $moduloActual = $controlador;
        $panelSesion  = false;
        $titulo       = 'Módulo en construcción';
        require BASE_PATH . '/views/common/en_construccion.php';
    }
} catch (Throwable $e) {
    // El detalle tecnico va al log SIEMPRE
    error_log(
        'Error en el sistema: ' . $e->getMessage()
        . ' @ ' . $e->getFile() . ':' . $e->getLine()
    );
    http_response_code(500);

    $detalle = '';
    if (APP_DEBUG) {
        // Desarrollo: mostrar el error para diagnosticar rapido.
        // En produccion APP_DEBUG=false y solo se ve el generico.
        $detalle = '<p>'
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</p>'
            . '<p style="color:#667;font-size:13px">'
            . htmlspecialchars(
                get_class($e)
                . ' @ ' . $e->getFile() . ':' . $e->getLine(),
                ENT_QUOTES,
                'UTF-8'
            )
            . '</p>'
            . '<p style="color:#667;font-size:13px">'
            . 'APP_DEBUG=true (config/database.php); '
            . 'en produccion debe ser false.</p>';
    }

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<title>Error</title></head>'
       . '<body style="font-family:Arial,sans-serif;padding:40px">'
       . '<h1>Error interno</h1>'
       . ($detalle !== ''
            ? $detalle
            : '<p>Inténtelo de nuevo más tarde.</p>')
       . '</body></html>';
}
