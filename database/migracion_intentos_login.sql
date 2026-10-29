-- ============================================================
-- MIGRACION: crear la tabla intentos_login
-- (auditoria de accesos + bloqueo por fuerza bruta)
--
-- Ejecutar SOLO si la base de datos ya existe (version previa
-- sin la tabla). Si vas a crear la BD desde cero, NO ejecutes
-- este archivo: farmacia_v2.sql ya la incluye.
-- ============================================================

CREATE TABLE IF NOT EXISTS intentos_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    exito TINYINT(1) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
