-- Añade el rol supervisor sin modificar los identificadores existentes.
INSERT INTO roles (id, nombre, descripcion)
SELECT 4, 'supervisor', 'Acceso a todos los árboles y gestión global de la genealogía'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE id = 4);

-- Si la tabla ya contiene el id 4 con otro nombre, lo normalizamos.
UPDATE roles
SET nombre = 'supervisor',
    descripcion = 'Acceso a todos los árboles y gestión global de la genealogía'
WHERE id = 4;
