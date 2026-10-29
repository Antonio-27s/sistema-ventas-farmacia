-- ============================================================
-- FARMACIA - ESQUEMA (campos, propiedades, UNIQUE y llaves foraneas)
-- BD: farmacia | MySQL 8.0+ | InnoDB | utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS farmacia
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE farmacia;


-- TABLA 1: ROL
CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    CONSTRAINT uq_rol_nombre UNIQUE (nombre)
) ENGINE=InnoDB;


-- TABLA 2: USUARIO
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    dni VARCHAR(8) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuario_login UNIQUE (usuario),
    FOREIGN KEY (id_rol) REFERENCES rol (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 3: CLIENTE
CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('DNI','CE','CARNET_EXTRANJERO','RUC') NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(20),
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100),
    apellido_materno VARCHAR(100),
    telefono VARCHAR(20),
    correo VARCHAR(150),
    direccion VARCHAR(200),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_cliente_documento UNIQUE (tipo_documento, numero_documento)
) ENGINE=InnoDB;


-- TABLA 4: CATEGORIA
CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    CONSTRAINT uq_categoria_nombre UNIQUE (nombre)
) ENGINE=InnoDB;


-- TABLA 5: LABORATORIO
CREATE TABLE laboratorio (
    id_laboratorio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    ruc VARCHAR(11),
    telefono VARCHAR(20),
    direccion VARCHAR(200),
    correo VARCHAR(150),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT uq_laboratorio_ruc UNIQUE (ruc)
) ENGINE=InnoDB;


-- TABLA 6: PRODUCTO
CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    id_laboratorio INT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    principio_activo VARCHAR(150),
    concentracion VARCHAR(100),
    presentacion VARCHAR(100),
    tipo_producto ENUM('MEDICAMENTO', 'DISPOSITIVO_MEDICO', 'HIGIENE',
                       'CUIDADO_PERSONAL', 'SUPLEMENTO', 'OTRO')
                   NOT NULL DEFAULT 'MEDICAMENTO',
    requiere_receta TINYINT(1) NOT NULL DEFAULT 0,
    precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_producto_codigo UNIQUE (codigo),
    FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_laboratorio) REFERENCES laboratorio (id_laboratorio)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLA 7: PROVEEDOR
CREATE TABLE proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(150) NOT NULL,
    ruc VARCHAR(11) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(200),
    correo VARCHAR(150),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT uq_proveedor_ruc UNIQUE (ruc)
) ENGINE=InnoDB;


-- TABLA 8: COMPRA
CREATE TABLE compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo_documento ENUM('FACTURA','BOLETA','OTRO') NOT NULL DEFAULT 'FACTURA',
    serie VARCHAR(10),
    numero VARCHAR(20),
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('REGISTRADA','ANULADA') NOT NULL DEFAULT 'REGISTRADA',
    CONSTRAINT uq_compra_documento UNIQUE (serie, numero),
    FOREIGN KEY (id_proveedor) REFERENCES proveedor (id_proveedor)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 9: DETALLE_COMPRA
CREATE TABLE detalle_compra (
    id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 10: LOTE
CREATE TABLE lote (
    id_lote INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_compra INT,
    numero_lote VARCHAR(100) NOT NULL,
    fecha_fabricacion DATE,
    fecha_vencimiento DATE NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_compra) REFERENCES compra (id_compra)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLA 11: STOCK
CREATE TABLE stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_stock_producto UNIQUE (id_producto),
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- TABLA 12: VENTA
CREATE TABLE venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_usuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo_pago ENUM('EFECTIVO','YAPE','PLIN','TARJETA','TRANSFERENCIA','QR') NOT NULL DEFAULT 'EFECTIVO',
    estado ENUM('COMPLETADA','ANULADA','DEVUELTA') NOT NULL DEFAULT 'COMPLETADA',
    FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
        ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 13: DETALLE_VENTA
CREATE TABLE detalle_venta (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    id_lote INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_lote) REFERENCES lote (id_lote)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLA 14: RECETA
CREATE TABLE receta (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    numero_receta VARCHAR(50),
    dni_paciente VARCHAR(20) NOT NULL,
    nombre_medico VARCHAR(150) NOT NULL,
    colegiatura VARCHAR(50),
    fecha DATE NOT NULL,
    observacion VARCHAR(500)
) ENGINE=InnoDB;


-- TABLA 15: DETALLE_RECETA
CREATE TABLE detalle_receta (
    id_detalle_receta INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    dosis VARCHAR(100),
    frecuencia VARCHAR(100),
    duracion VARCHAR(100),
    FOREIGN KEY (id_receta) REFERENCES receta (id_receta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 16: BOLETA
CREATE TABLE boleta (
    id_boleta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dni_cliente VARCHAR(20),
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
    CONSTRAINT uq_boleta_venta UNIQUE (id_venta),
    CONSTRAINT uq_boleta_documento UNIQUE (serie, numero),
    FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 17: FACTURA
CREATE TABLE factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ruc VARCHAR(11) NOT NULL,
    razon_social VARCHAR(200) NOT NULL,
    direccion VARCHAR(250),
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
    CONSTRAINT uq_factura_venta UNIQUE (id_venta),
    CONSTRAINT uq_factura_documento UNIQUE (serie, numero),
    FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 18: CAJA
CREATE TABLE caja (
    id_caja INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_ventas DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    otros_ingresos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    otros_egresos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_final DECIMAL(10,2),
    fecha_cierre DATETIME,
    estado ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 19: MOVIMIENTO_CAJA
CREATE TABLE movimiento_caja (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_caja INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('INGRESO','EGRESO') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_caja) REFERENCES caja (id_caja)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 20: DEVOLUCION
CREATE TABLE devolucion (
    id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(255) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- TABLA 21: DETALLE_DEVOLUCION
CREATE TABLE detalle_devolucion (
    id_detalle_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_devolucion INT NOT NULL,
    id_producto INT NOT NULL,
    id_lote INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_devolucion) REFERENCES devolucion (id_devolucion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_lote) REFERENCES lote (id_lote)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLA 22: INTENTOS_LOGIN
-- Auditoria de accesos + bloqueo por fuerza bruta
-- (5 fallos/15 min por usuario o 20/15 min por IP, en la app)
CREATE TABLE intentos_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    exito TINYINT(1) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- INSERT 1: ROLES DEL SISTEMA
INSERT IGNORE INTO rol (nombre, descripcion)
VALUES
    ('Administrador', 'Control total del sistema'),
    ('Vendedor', 'Realiza ventas y registra clientes'),
    ('Almacenero', 'Gestiona compras e inventario');


-- INSERT 2: CATEGORIAS INICIALES DE PRODUCTOS
INSERT IGNORE INTO categoria (nombre, descripcion)
VALUES
    ('Antibioticos', 'Infecciones bacterianas'),
    ('Analgésicos', 'Alivio del dolor'),
    ('Antiinflamatorios', 'Inflamacion y dolor (AINEs)'),
    ('Cardiovasculares', 'Presion, colesterol, anticoagulantes'),
    ('Gastrointestinales', 'Acidez, digestión, diarrea'),
    ('Respiratorios', 'Resfrio, tos, asma, alergias'),
    ('Vitaminas y Suplementos', 'Vitaminas, minerales, aminoacidos'),
    ('Dermatologicos', 'Uso topico, pieles y heridas'),
    ('Higiene y Cuidado Personal', 'Articulos de higiene y belleza'),
    ('Dispositivos Medicos', 'Tensimetros, termometros, inyectores');


-- INSERT 3: USUARIO POR DEFECTO (ADMINISTRADOR)
-- Reemplaza el password por el hash bcrypt/argon2 real generado en tu app.
-- INSERT INTO usuario (id_rol, nombre, apellido_paterno, apellido_materno,
--                      usuario, password)
-- VALUES (1, 'Sistema', 'Administrador', 'Principal', 'admin',
--         '$2b$10$...hash_bcrypt_generado_en_las_app...');
