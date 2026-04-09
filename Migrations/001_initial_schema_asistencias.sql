CREATE TABLE IF NOT EXISTS "Sesion" (
    id SERIAL PRIMARY KEY,
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    estado VARCHAR(50) DEFAULT 'abierta' NOT NULL,
    observaciones TEXT NULL,
    encargado_apertura_id INT NOT NULL,
    encargado_cierre_id INT NULL
);
CREATE TABLE IF NOT EXISTS "Asistencia" (
    id SERIAL PRIMARY KEY,
    fecha_llegada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_salida TIMESTAMP NULL,
    estado VARCHAR(50) DEFAULT 'presente' NOT NULL,
    observaciones TEXT NULL,
    encargado_id INT NULL,
    estudiante_id INT NULL,
    sesion_id INT NOT NULL,
    FOREIGN KEY (sesion_id) REFERENCES "Sesion"(id) ON DELETE CASCADE
);
