CREATE TABLE IF NOT EXISTS "Sesion" (
    id SERIAL PRIMARY KEY,
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    estado VARCHAR(50) DEFAULT 'abierta' NOT NULL,
    observaciones TEXT NULL,
    encargado_apertura_id INT NOT NULL,
    encargado_cierre_id INT NULL
);
CREATE TABLE IF NOT EXISTS "Qr" (
    id SERIAL PRIMARY KEY,
    token VARCHAR(255) UNIQUE NOT NULL, 
    objetivo VARCHAR(50) NOT NULL,  
    estado VARCHAR(50) NOT NULL DEFAULT 'activo',
    sesion_id INT NOT NULL,
    FOREIGN KEY (sesion_id) REFERENCES "Sesion"(id) ON DELETE CASCADE
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
    es_cerrado_por_sistema BOOLEAN DEFAULT false,
    qr_entrada_id INT,
    qr_salida_id INT,
    FOREIGN KEY (qr_entrada_id) REFERENCES "Qr"(id),
    FOREIGN KEY (qr_salida_id) REFERENCES "Qr"(id),
    FOREIGN KEY (sesion_id) REFERENCES "Sesion"(id) ON DELETE CASCADE
);
