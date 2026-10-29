-- ============================================================
-- MIGRACION: agregar el campo DNI a la tabla `usuario`
--
-- Ejecutar SOLO si la base de datos ya existe (version previa
-- sin el campo). Si vas a crear la BD desde cero, NO ejecutes
-- este archivo: farmacia_v2.sql ya incluye el campo.
-- ============================================================

ALTER TABLE usuario
    ADD COLUMN dni VARCHAR(8) NOT NULL DEFAULT '' AFTER apellido_materno;

-- Asignar el DNI al usuario demo existente
UPDATE usuario
SET dni = '45678912'
WHERE usuario = 'admin';
