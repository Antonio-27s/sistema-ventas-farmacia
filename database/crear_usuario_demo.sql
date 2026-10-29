-- ============================================================
-- USUARIOS DEMO PARA PROBAR EL LOGIN Y EL RBAC
-- (hashes bcrypt cost 10, verificados con password_verify)
--
-- Administrador : admin  / DNI 45678912 / Admin123!
-- Vendedor      : vdemo  / DNI 12345678 / Vendedor123!
-- Almacenero    : ademo  / DNI 87654321 / Almacenero123!
-- ============================================================

INSERT IGNORE INTO usuario
    (id_rol, nombre, apellido_paterno, apellido_materno, dni, usuario, password)
VALUES
    (1, 'Sistema', 'Administrador', 'Principal', '45678912', 'admin',
     '$2b$10$EdPqVAXja3Vl6E5.DqJuROw.kwAmWMX7MLkPIcmNVpftcZQqWSp.y'),
    (2, 'Demo', 'Vendedor', 'Prueba', '12345678', 'vdemo',
     '$2b$10$ePUTjhKWbAwqEx48xBvb2uydiY7HTy00znr6qOTeMDIDztx6NkJUS'),
    (3, 'Demo', 'Almacenero', 'Prueba', '87654321', 'ademo',
     '$2b$10$KCktQ/8n0P5okK6yLIrJMOcMEk5ecEOIxNNJ9MhChcaXMI4xvEOXS');
