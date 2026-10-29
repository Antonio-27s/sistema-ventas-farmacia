# Sistema de Ventas — Farmacia (PHP, MVC por incrementos)

Sistema web de ventas para farmacia, desarrollado en PHP nativo con
arquitectura **MVC** y crecido **por incrementos pequeños** (cada
incremento agrega una pieza verificable sobre la anterior, sin
reescribir lo anterior).

- Sin frameworks y sin dependencias externas: solo PHP 8 + PDO + MySQL.
- Login con usuario + **DNI (8 dígitos)** + tipo de usuario + contraseña.
- Roles con control de acceso (RBAC) verificado en el servidor en cada
  petición: Administrador, Vendedor y Almacenero.
- Estilos propios azules/morados (`public/css/styles.css`), con el
  respaldo para imagen de fondo en `public/css/imagen/fondo.jpg`
  (opcional; se aplica solo si existe).

---

## Estado actual (incremento 6.1 — v1.10.0)

| # | Incremento | Estado |
|---|---|---|
| 1 | Estructura MVC + config + conexión BD | ✅ |
| 2 | Login (usuario + DNI + tipo + contraseña) | ✅ |
| 3 | Recordar mi sesión 14 días | ✅ |
| 4 | Logout con destrucción de sesión y cookies | ✅ |
| 5 | Endurecimiento A: headers, revalidación de CSRF | ✅ |
| 5.1 | Endurecimiento B: Argon2id, intentos de login, timeout | ✅ |
| 6 | Gestión de usuarios (módulo Usuarios, solo Admin) | ✅ |
| **6.1** | **Usuarios: crear y editar cuentas (v1.10.0)** | ✅ |
| 7 | Dashboard (aside + header) + panel de sesión | ✅ |
| 7.1 | Corrección de `obtenerConRol` (v1.6.1) | ✅ |
| 8.1 | Módulo Producto: catálogo, formulario, categorías, laboratorios (v1.7.0) | ✅ |
| 8.1.1 | Reorganización de vistas por módulo + JS puro por módulo (v1.8.0) | ✅ |
| **8.2** | **Módulos Stock y Lote + validación de forms sin recargar (v1.9.0)** | ✅ |
| **8.2.1** | **Bugfix del catálogo + limpieza de estructura (v1.9.1)** | ✅ |
| 8.3 | Compra (usa `lote.id_compra`, reservado para esto) | ⏳ |
| 8.4 | Cliente | ⏳ |
| 8.5 | Venta (POS; el stock se descuenta en la misma transacción) | ⏳ |
| 8.6 | Receta | ⏳ |
| 8.7 | Caja | ⏳ |
| 8.8 | Reporte (solo Administrador) | ⏳ |

---

## Incremento 8.2 — Stock + Lote (v1.9.0)

Nuevo en este incremento:

**1. Módulo Stock** (`?controlador=Stock` — Administrador y Almacenero)

- Listado de stock por producto con búsqueda (nombre/código) y filtro
  por estado: Adecuado, Bajo o Agotado.
- Estado derivado de la BD: `cantidad = 0` → **Agotado**;
  `cantidad < stock_minimo` → **Bajo**; lo demás **Adecuado**.
- **Ajuste manual de cantidad** por fila (input + botón, CSRF,
  validación 0–99999999). Por ahora es la única entrada de stock;
  compras y ventas llegarán en 8.3/8.5 **en transacción**.
- Con JS: la fila se actualiza en el lugar (cantidad, estado y última
  fecha) sin recargar la página.

**2. Módulo Lote** (`?controlador=Lote` — Administrador y Almacenero)

- Listado **FEFO** (First Expired, First Out): el lote que vence
  primero va primero en la tabla — es el orden que usará el POS
  para vender.
- Estado por vencimiento: **Vencido**, **Vence pronto** (≤ 30 días,
  constante `Lote::DIAS_PRONTO`) y **Vigente**.
- **Registro de lote**: producto, número de lote, fecha de
  fabricación (opcional), fecha de vencimiento (obligatoria) y
  cantidad. Al guardar, la cantidad **entra al stock del producto en
  la misma transacción** (`Lote::crear`). El campo `id_compra` está
  reservado para el módulo Compra (8.3).

**3. Validación de capacidad de caracteres sin recargar**
(convención permanente a partir de ahora)

- El servidor pinta cada campo con `maxlength` igual a la longitud de
  su columna en la BD (fuente única del modelado).
- `public/js/app/validacion-forms.js` (global, lo carga
  `views/partes/cabecera.php` y la vista de login) añade:
  - un **contador vivo `n / max`** bajo cada campo de texto o
    contraseña con `maxlength` (se pone rojo al llegar al límite);
    se puede desactivar por campo con `data-sin-contador` (caso: DNI);
  - **validación de dígitos** con `data-digitos="N"`: solo acepta
    caracteres numéricos mientras se escribe y exige exactamente N
    dígitos (marca el campo, bloquea el envío y muestra el error
    bajo el campo). Aplicado hoy: DNI del login (8) y RUC de
    laboratorios (11, opcional → exacto o vacío).
- Sin recargar la página: todo el feedback es inmediato; si el JS
  está desactivado, `maxlength` + `pattern` siguen limitando y el
  servidor revalida siempre (defensa en profundidad: **el cliente
  valida en vivo, el servidor decide**).

**4. JS del shell centralizado**

- `public/js/app/api.js`: ayuda compartida `window.API` para
  `fetch` (GET/POST form-urlencoded) que añade el token CSRF del
  `<meta name="csrf-token">` y convierte las respuestas en objetos.
- El catálogo (Producto) usa ya esta ayuda; los de Stock y Lote la
  estrenan.

Cambios en los archivos:

- Nuevos: `models/Stock.php`, `models/Lote.php`,
  `controllers/StockController.php`, `controllers/LoteController.php`,
  `views/stock/stock.php`, `views/lote/lote.php`,
  `views/lote/lote_form.php`, `public/js/stock/stock.js`,
  `public/js/lote/lote.js`, `public/js/app/api.js`,
  `public/js/app/validacion-forms.js`.
- Modificados: `public/index.php` (ramas Stock y Lote),
  `views/partes/cabecera.php` (carga `api.js` y
  `validacion-forms.js`), `views/inicio/inicio.php` (modulos hechos),
  `views/auth/login.php` (script + `data-digitos` en DNI),
  `views/producto/producto_laboratorios.php` (`data-digitos` en RUC),
  `public/js/producto/catalogo.js` (usa `window.API`),
  `public/css/styles.css` (badge warn, contador, error-campo,
  ajusta-cant), `config/database.php` (versión 1.9.0).

RBAC: la matriz de `models/Rol.php` ya otorgaba **Stock** y **Lote** a
Administrador y Almacenero; este incremento las hace reales.
El Almacenero ve en el aside: Producto, Stock y Lote.

---
---

## Incremento 8.2.1 — Bugfix del catálogo + limpieza (v1.9.1)

**Bugfix (lo que rompía el catálogo)**: `ProductoController::listar()`
guardaba el listado en `$filas`, pero la vista
(`views/producto/producto_catalogo.php`) espera `$productos` →
`Warning: Undefined variable` + `TypeError` en `count()`. La misma
auditoría encontró el mismo patrón en los re-llenos tras error de
`guardarCategoria()` y `guardarLaboratorio()` (la vista usa `$id` y
`$msg`, que no se definían al re-renderizar): corregidos también.
**Convención reforzada**: cada vista documenta al inicio qué
variables espera, y el controlador debe definirlas SIEMPRE (en todos
los caminos: éxito, JSON y re-lleno tras error).

**Limpieza de estructura y código**:

- `lib/app.php` (nuevo): helpers compartidos cargados por
  `public/index.php`.
  - `contextoVista($modulo, $titulo, $panelSesion = false)`: las 6
    variables del shell (`$usuario`, `$rolNombre`, `$modulosAside`,
    `$moduloActual`, `$panelSesion`, `$titulo`) que antes se
    copiaban en 16 lugares; ahora cada controlador hace una sola
    línea: `extract(contextoVista('Producto', 'Catálogo'));`.
  - `respuestaJson($datos)`: la respuesta JSON estándar (antes
    copiada 3 veces, una por controlador).
- `ProductoController`: `guardarProducto()` valida una sola vez
  (antes llamaba a `validarProducto()` dos veces); el patrón
  "JSON o 302 msg=error" de `toggleEstado()` se resume en
  `denegar()`; mismos `denegar()` en `StockController::ajustar()`.
- `LoteController`: la validación del lote sale a `validarLote()` +
  `fechaValida()` (antes inline).
- Aplicable a los 5 controladores con shell: Inicio, Usuario,
  Usuarios, Producto, Stock y Lote.

Cambios en los archivos: `lib/app.php` (nuevo), `public/index.php`
(carga el helper), `controllers/{Producto,Stock,Lote,Inicio,
Usuario,Usuarios}Controller.php` (cleanup + bugfix),
`config/database.php` (versión 1.9.1).

---

## Incremento 6.1 — Crear y editar usuarios (v1.10.0)

El módulo Usuarios (solo Administrador) ya listaba cuentas,
activaba/desactivaba y definía contraseñas. Con este incremento
queda el CRUD completo de cuentas:

**1. Alta de usuario** (`?controlador=Usuarios&accion=nuevo`)

- Formulario con: login, DNI (8 dígitos, validación en vivo con
  `data-digitos`), nombre + apellidos, rol y **contraseña inicial**
  (mínimo 8, confirmada).
- Unicidad de login (UNIQUE de la BD) y de DNI (verificada en la
  aplicación: una persona, una cuenta).
- El hash se genera con `Usuario::crearHash()` (Argon2id,
  respaldo bcrypt) en el servidor.

**2. Edición de usuario** (`?controlador=Usuarios&accion=editar&id=`)

- Los mismos campos, menos la contraseña: la edición de perfil
  **nunca** toca la contraseña (sigue la regla del sistema: la
  define solo el Administrador, al crear la cuenta o por «Definir
  contraseña» desde el listado).
- **Anti lockout**: sobre tu propia cuenta, el rol y «cuenta
  activa» se bloquean (disabled + hidden con el valor actual); el
  servidor lo revalida en todo caso.

**3. Listado**: botón «+ Nuevo usuario» y acción «Editar» por fila.

Cambios en los archivos: `models/Usuario.php` (+`existeLogin`,
+`existeDni`, +`crear`, +`actualizar`), `models/Rol.php`
(+`listar`), `controllers/UsuariosController.php` (+`formUsuario`,
+`guardarUsuario`, +`validarUsuario`, +`camposUsuario`),
`public/index.php` (whitelist: `nuevo`, `editar`, `guardar`),
`views/usuarios/usuario_form.php` (nueva),
`views/usuarios/usuarios.php` (botones), `config/database.php`
(versión 1.10.0).


## Estructura de carpetas

```
sistema_ventas/
├── config/
│   ├── database.php            → conexión PDO única + constantes de la app
│   └── esquema.php             → verificación automática del esquema (intento
│                                 de login / columnas nuevas; v1.5.0+)
├── lib/
│   └── app.php                 → helpers compartidos: contextoVista() y
│                                 respuestaJson() (desde v1.9.1)
├── controllers/
│   ├── AuthController.php      → login, logout, guard de sesión
│   ├── InicioController.php    → dashboard (modulos hechos vs. pendientes)
│   ├── UsuarioController.php   → "Ver perfil" + panel temporal de sesión
│   ├── UsuariosController.php  → CRUD de usuarios (solo Administrador)
│   ├── ProductoController.php  → catálogo + categorías + laboratorios
│   ├── StockController.php     → stock por producto + ajuste (nuevo 8.2)
│   └── LoteController.php      → lotes FEFO + registro (nuevo 8.2)
├── models/
│   ├── Usuario.php             → autenticación, hash, roles, panel de sesión
│   ├── Rol.php                 → matriz de permisos RBAC + módulos por rol
│   ├── IntentoLogin.php        → registro/limpieza de intentos fallidos
│   ├── Categoria.php           → CRUD de categorías
│   ├── Laboratorio.php         → CRUD de laboratorios
│   ├── Producto.php            → catálogo (crear/editar/búsqueda/estado)
│   ├── Stock.php               → listado con estados + ajuste (nuevo 8.2)
│   └── Lote.php                → listado FEFO + registro con transacción
├── views/
│   ├── partes/                 → cabecera.php (aside+header, carga los JS del
│   │                             shell) y pie.php (pie de página)
│   ├── common/                 → error500.php y modulos.php (vista de
│   │                             "en construcción")
│   ├── auth/login.php          → login (DNI 8 dígitos + tipo + 14 días)
│   ├── inicio/inicio.php       → dashboard
│   ├── usuario/perfil.php      → "Ver perfil"
│   ├── usuarios/               → usuarios.php (listado) y
│   │                             usuario_form.php (alta/edición)
│   ├── producto/               → producto_catalogo.php, producto_form.php,
│   │                             producto_categorias.php,
│   │                             producto_laboratorios.php
│   ├── stock/stock.php         → listado + ajuste (nuevo 8.2)
│   └── lote/                   → lote.php (listado FEFO) y
│                                 lote_form.php (nuevo 8.2)
├── database/
│   ├── farmacia_v2.sql         → esquema completo (22 tablas, 13 unique,
│   │                             27 FK, 4 índices)
│   ├── crear_usuario_demo.sql  → hash demo (bcrypt compatible)
│   ├── demo_datos.sql          → datos de ejemplo (16 productos, 12
│   │                             lotes, 18 stocks)
│   ├── alter_login_hardening.sql→ intentos_login + limpiar intentos
│   └── migration_v2.sql        → alter para bases antiguas
├── public/                     → raiz web (docroot apunta aca)
│   ├── index.php               → front controller (router por ?controlador=)
│   ├── diagnostico.php         → diagnostico de despliegue (solo APP_DEBUG)
│   ├── css/styles.css
│   ├── css/imagen/             → (opcional) fondo.jpg
│   └── js/
│       ├── app/                → panel-sesion.js, api.js, validacion-forms.js
│       ├── producto/catalogo.js
│       ├── stock/stock.js      → (nuevo 8.2)
│       └── lote/lote.js        → (nuevo 8.2)
└── README.md
```

### Convención de vistas y JavaScript (desde 8.1.1, vigente)

- Cada módulo tiene su carpeta de vistas `views/{modulo}/` (las
  comunes van a `views/common/`, el shell a `views/partes/`).
- La parte dinámica de cada vista la hace **JavaScript puro, legible
  y fácil de mantener** en `public/js/{modulo}/{nombre}.js`; los
  scripts del shell global van a `public/js/app/`.
- El HTML se renderiza siempre en el servidor (progressive
  enhancement): sin JS el sistema sigue funcionando con recargas.
- El JS nunca intercala datos de la BD en `innerHTML`; construye el
  DOM con `createElement`/`textContent`.
- **Validación de capacidad según el modelado**: `maxlength` =
  longitud de columna; contador vivo y `data-digitos="N"` con
  `validacion-forms.js` (desde 8.2, ver arriba).

---

## Requisitos

- PHP 8.x con `pdo_mysql`.
- MySQL/MariaDB en `localhost` **puerto 3308**.
- Usuario BD `root` sin contraseña (ajustable en `config/database.php`).

## Instalación

1. **Base de datos** — importar `database/farmacia_v2.sql` en el
   puerto 3308 (phpMyAdmin o `mysql -P 3308`).

2. **Usuario administrador demo** (opcional, para probar rápido):
   importar `database/crear_usuario_demo.sql` y, si se desea datos
   de catálogo, `database/demo_datos.sql`.
   Credenciales demo:
   - `admin / 45678912 / Admin123!` (Administrador)
   - `vdemo / 12345678 / Vendedor123!` (Vendedor)
   - `ademo / 87654321 / Almacenero123!` (Almacenero)

3. **Código** — copiar esta carpeta a `www/sistema_ventas` (el
   docroot de Apache es `www/`). El sistema queda en
   `http://localhost/sistema_ventas/public/`.

4. **Primer inicio** — si la BD no tiene la tabla `intentos_login`
   o `usuario.dni`, la app responde 500 con un mensaje que indica
   exactamente qué falta y qué importar (el esquema se verifica de
   forma automática en cada login, sin costo cuando todo está).

## Uso

- `?controlador=Inicio` (default) → dashboard con módulos hechos y
  pendientes, según el rol.
- `?controlador=Usuarios` → CRUD de usuarios (solo Administrador).
- `?controlador=Producto` → catálogo con búsqueda, filtro por
  categoría, alta/edición, activar/inactivar, y administración de
  categorías y laboratorios.
- `?controlador=Stock` → stock por producto: estados (Adecuado /
  Bajo / Agotado), filtros y ajuste de cantidad por fila.
- `?controlador=Lote` → lotes en orden FEFO con estados de
  vencimiento; `&accion=nuevo` registra un lote (su cantidad entra
  al stock en la misma transacción).
- Header → **Ver perfil**, **Ver sesión** (panel temporal) y
  **Cerrar sesión**.
- Login → usuario + DNI + tipo + contraseña, con "Recordar mi sesión
  por 14 días" (si no se marca: solo durante la sesión actual).

## Seguridad (resumen)

- Contraseñas: **Argon2id** (con fallback a bcrypt si la compilación
  de PHP no lo trae; rehash transparente al migrar).
- Tokens CSRF en todos los POST (form y fetch; rotado tras el login,
  revalidado contra BD en cada petición).
- Brute-force: 5 fallidas → bloqueo 15 min por usuario;
  20 fallidas → 15 min por IP.
- Timeout de sesión por inactividad: 10 minutos.
- Headers: `Content-Security-Policy`, `X-Content-Type-Options`,
  `Referrer-Policy`; cookies `HttpOnly` + `SameSite=Lax`.
- RBAC por matriz en `models/Rol.php`: deny-by-default, verificado
  en el servidor por petición; el nombre del rol siempre viene de la BD.
- Recuperación de contraseña: **solo el Administrador**, definiendo
  directamente la nueva (no hay auto-servicio ni contraseñas
  temporales).
- Salidas HTML escapadas con `htmlspecialchars`; consultas con PDO
  preparado.

---

## Cómo probar el sistema

El sistema no usa JSON de forma general: las páginas son HTML
renderizado en el servidor y los formularios viajan con el método
clásico `POST` (`application/x-www-form-urlencoded`). El **único**
JSON que existe es opcional: el listado de productos y el
activar/inactivar aceptan `&json=1` en la URL para que el JS del
módulo pueda trabajar sin recargar (lo mismo hará Stock/Lote).
Esto hace que probarlo sea sencillo desde varias herramientas.

### 1. Navegador (flujo manual) — la forma principal

- **Login**: `admin / 45678912 / Admin123!` (tipo Administrador).
  Prueba también el "Recordar mi sesión por 14 días" (la cookie
  `SV_FARMACIA_SES` queda con expiración +14 días; sin la casilla,
  es cookie de sesión).
- **RBAC**: entra con `ademo / 87654321 / Almacenero123!` y verifica
  que el aside solo muestra Producto, Stock y Lote; con `vdemo /
  12345678 / Vendedor123!` que no ve Usuarios; y que forzar la URL
  `?controlador=Usuarios` como Almacenero da acceso denegado.
- **Validación en vivo (8.2)**: en el login, el DNI solo acepta
  dígitos y marca el error sin recargar; en laboratorios, el RUC
  exige 11 dígitos si se llena; en los formularios verás el
  contador `n / max` bajo cada campo de texto.
- **Stock**: cambia la cantidad de un producto; la fila debe
  actualizar cantidad/estado/fecha sin recargar; filtra por
  "Agotado" (pasa cualquier producto a 0 primero).
- **Lote**: registra un lote (fecha de vencimiento dentro de 30 días
  → badge "Vence pronto"; en el pasado → "Vencido"); verifica que
  aparezca primero en el listado (FEFO) y que el stock del producto
  subió en el módulo Stock.
- **Sin JavaScript**: desactiva JS (DevTools → Desactivar
  JavaScript, o una ventana incógnita con la extensión) y todo sigue
  funcionando con recargas: login, listados, formularios, ajuste de
  stock (botón real que POSTea) y registro de lote. Es el
  progressive enhancement por diseño.
- **Timeout**: espera 10 minutos sin hacer nada → la sesión caduca
  y el siguiente click lleva al login.
- **Logout**: "Cerrar sesión" destruye sesión, CSRF y cookies.

### 2. Postman

Funciona perfecto gracias al modelo mixto (páginas HTML + peticiones
form clásicas + JSON opcional):

1. **Login** (GET `http://localhost/sistema_ventas/public/index.php`)
   y **extrae el token CSRF** del HTML:
   `<input type="hidden" name="csrf" value="…">`. Postman guarda la
   cookie de sesión (`SV_FARMACIA_SES`) sola en su cookie jar
   (deja activo "Cookies" en la pestaña de la petición).
2. **POST** a `…/index.php?controlador=Auth&accion=procesar` con
   body *form-urlencoded*: `usuario`, `dni`, `tipo`
   (Administrador|Vendedor|Almacenero), `password`, `remember` y
   `csrf` (el extraído). Responde `302` → el dashboard.
3. **Importante**: el token CSRF **rota al entrar**, así que para
   cualquier POST posterior (guardar producto, ajustar stock,
   registro de lote, toggle…) vuelve a hacer un GET a una página
   autenticada (ej. el dashboard) y toma el token del
   `<meta name="csrf-token" content="…">` — el mismo que usan los
   fetch del navegador. Los GET de listado no llevan CSRF.
4. **Ejemplos útiles**:
   - Listado de productos: GET `?controlador=Producto` (HTML) o
     `?controlador=Producto&json=1` (JSON).
   - Ajustar stock: POST `?controlador=Stock&accion=ajustar&json=1`
     form-urlencoded con `csrf`, `id_producto`, `cantidad` →
     `{"ok":true,"cantidad":…, "estado":"BAJO","fecha":"…"}`.
   - Toggle producto: POST `?controlador=Producto&accion=toggle_estado&json=1`
     con `csrf` y `producto_id`.
   - Registrar lote: POST `?controlador=Lote&accion=guardar` con
     `csrf`, `id_producto`, `numero_lote`, `fecha_fabricacion`,
     `fecha_vencimiento` (`Y-m-d`), `cantidad` → `302` a
     `?controlador=Lote&msg=lote-ok`.
5. Para automatizar, puedes usar la *pre-request script* de la
   colección para leer el meta csrf del último GET, o simplemente
   re-extraerlo después del login (cambia una sola vez por sesión).

### 3. curl / PowerShell (WAMP)

Equivalentes directos a lo anterior (Windows, PowerShell):

```powershell
# 1) Login: extraer csrf del HTML
$html = (Invoke-WebRequest -SessionVariable s -UseBasicParsing `
    "http://localhost/sistema_ventas/public/index.php").Content
$csrf = ([regex]'<input type="hidden" name="csrf" value="([^"]+)"').Match($html).Groups[1].Value

# 2) POST del login (la cookie viaja con -SessionVariable)
Invoke-WebRequest -WebSession $s -UseBasicParsing -Method POST `
    -Uri "http://localhost/sistema_ventas/public/index.php?controlador=Auth&accion=procesar" `
    -Body @{ usuario="admin"; dni="45678912"; tipo="Administrador";
             password="Admin123!"; csrf=$csrf } | Out-Null
```

En `curl` (Git Bash / WSL):

```bash
C=cookies.txt
CSRF=$(curl -s -c $C http://localhost/sistema_ventas/public/index.php \
  | grep -oP 'name="csrf" value="\K[^"]+')
curl -s -b $C -c $C -d "usuario=admin&dni=45678912&tipo=Administrador&password=Admin123!&csrf=$CSRF" \
  "http://localhost/sistema_ventas/public/index.php?controlador=Auth&accion=procesar" \
  -o /dev/null -w "%{http_code} %{redirect_url}\n"
```

Después de entrar, el csrf rota: repite el grep de un GET
autenticado (o lee el `<meta name="csrf-token">`) para los POST.

### 4. PHP CLI (scripts fuera del docroot)

El WAMP incluye PHP; para consultas puntuales a la BD sin tocar el
docroot, guarda el script **fuera de `www/`** (ej. `C:\sistema\check.php`):

```php
<?php
$pdo = new PDO('mysql:host=localhost;port=3308;dbname=farmacia;charset=utf8mb4',
               'root', '');
echo $pdo->query('SELECT COUNT(*) FROM lote')->fetchColumn() . " lotes\n";
```

y ejecútalo con `C:\wamp64\bin\php\php8.2.26\php.exe C:\sistema\check.php`.
Útil para verificar que un lote quedó grabado, que el stock sumó,
o para simular el efecto de una venta antes de 8.5.

### 5. La BD como oráculo (phpMyAdmin)

Muchas pruebas del sistema se verifikaran mejor en la propia BD:

- **Stock**: `SELECT * FROM stock ORDER BY cantidad;` después de
  cada ajuste; `UPDATE stock SET cantidad=0 WHERE id_producto=1;`
  para forzar "Agotado" y probar el estado.
- **Lotes**: `SELECT * FROM lote ORDER BY fecha_vencimiento;` debe
  coincidir con el orden FEFO de la tabla de la app;
  `INSERT INTO lote (id_producto, numero_lote, fecha_vencimiento, cantidad)
  VALUES (1, 'TEST-1', CURDATE(), 10);` y luego abrir el módulo
  Stock: la cantidad debe haber subido 10 (transacción de
  `Lote::crear`).
- **Constraints**: al ser un "oráculo", la BD es la última palabra:
  los 13 `uq_*` (ej. `uq_lote_id_producto`, `uq_productos_codigo`) y
  las 27 FK (lote→producto `RESTRICT`, lote→compra `SET NULL` para
  8.3) te dicen si un estado de la app es realmente coherente.
- **Datos demo**: `database/demo_datos.sql` carga 16 productos, 12
  lotes y 18 stocks para que los módulos Product/Stock/Lote se
  vean con contenido desde el primer login.

### 6. DevTools (pestaña Network)

- F12 → Network y opera el catálogo/stock/lotes: las búsquedas y
  ajustes son peticiones `fetch` (verás el `&json=1` y el
  `X-CSRF-Token` en el header) que responden JSON sin navegar.
- Permite verificar que **no** hay recargas (el documento no se
  vuelve a pedir), que el estado HTTP es 200, y el contenido exacto
  del JSON. La consola muestra errores de JS en rojo (debido a
  `console.error`, que hay que leer antes que asumir que está
  roto).
- Application → Cookies para inspeccionar `SV_FARMACIA_SES`
  (HttpOnly, SameSite=Lax) y su expiración según "recordarme".

### 7. Escenarios de seguridad (de la sección anterior)

- 5 logins fallidos con `admin` → la 6ª da "demasiados intentos,
  bloqueado 15 min" y `SELECT * FROM intentos_login` lo registra.
- Un POST con `csrf` antiguo (el del paso 1, ya rotado) →
  "formulario expiró o es inválido".
- `?controlador=Usuarios` como Almacenero → denegado (matriz RBAC).
- 10 min de inactividad → caducidad de sesión.

### 8. Punto de diagnóstico

Con `APP_DEBUG = true`, `public/diagnostico.php` (403 si está en
false) muestra en una página la versión de la app desplegada, la
config usada y la ruta de cada pieza del sistema (BD, login,
schema). Es la forma más rápida de responder "¿estoy viendo la
versión correcta?" después de un redeploy.

---

## Nota de despliegue (WAMP) — anti "stale"

La app queda en `www/sistema_ventas`; el docroot de Apache es
`www/`. Para re-desplegar **siempre**:

1. Borrar la carpeta `www/sistema_ventas` completa y copiar la
   versión nueva (nunca copiar encima sin borrar: quedan archivos
   viejos que producen "media" versiones).
2. Reiniciar Apache desde el control de WAMP (los scripts PHP se
   relee en cada petición, pero el orden de carga de módulos y las
   cachés del browser no perdonan).
3. Cargar con **Ctrl+F5** (o una ventana incógnita).
4. Verificar con el sello de versión: el pie de la app muestra
   `sistema_ventas v1.10.0` (constante `APP_VERSION` en
   `config/database.php`). Si el pie no coincide con lo esperado,
   abre `public/diagnostico.php` (403 si `APP_DEBUG` está en false).
5. Si un cambio "no se ve" y el sello sí es el correcto, el problema
   está en la BD (falta un SQL pendiente de importar), no en el código.

---

## Próximos incrementos

- **8.3** Compra: compra de insumos por lote (usa `lote.id_compra`,
  ya reservado en el esquema).
- **8.4** Cliente: registro y búsqueda de clientes.
- **8.5** Venta: POS con descuento por FEFO y descuento de stock en
  la misma transacción.
- **8.6** Receta: recetas con medicamentos que `requiere_receta`.
- **8.7** Caja: apertura/cierre de caja y movimientos.
- **8.8** Reporte: reportes (solo Administrador).
