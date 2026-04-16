-- Índices para optimizar consultas frecuentes en las tablas
-- Sesion, Qr y Asistencia. Se agregan sobre columnas utilizadas en
-- cláusulas WHERE, JOIN y ORDER BY, y sobre llaves foráneas.
--
-- Nota: la columna "Qr".token ya posee un índice único implícito
-- creado por la restricción UNIQUE, por lo que no se duplica aquí.
-- Las llaves primarias (id) también están indexadas automáticamente.

-- =============================================================
-- Tabla: Sesion
-- =============================================================
-- Usada en: obtenerUltimaSesionDadoEstado (WHERE estado = ? ORDER BY id DESC)
CREATE INDEX IF NOT EXISTS idx_sesion_estado
    ON "Sesion" (estado);

-- Llaves foráneas lógicas a encargados (optimizan filtros/joins futuros)
CREATE INDEX IF NOT EXISTS idx_sesion_encargado_apertura_id
    ON "Sesion" (encargado_apertura_id);

CREATE INDEX IF NOT EXISTS idx_sesion_encargado_cierre_id
    ON "Sesion" (encargado_cierre_id);

-- =============================================================
-- Tabla: Qr
-- =============================================================
-- Usada en múltiples consultas:
--   - obtenerQrsDeSesion / existeQrEnSesion (WHERE sesion_id = ?)
--   - obtenerQrsActivosDeSesion (WHERE sesion_id = ? AND estado = ? AND objetivo IN (...))
--   - cambiarEstadoQrsActivos (WHERE sesion_id = ? AND objetivo = ? AND estado = ?)
--   - marcarQrsInactivosPorSistema (WHERE sesion_id = ? AND estado = ?)
CREATE INDEX IF NOT EXISTS idx_qr_sesion_id
    ON "Qr" (sesion_id);

-- Usada en: existeQrConMismoObjetivoActivo (WHERE objetivo = ? AND estado = ?)
CREATE INDEX IF NOT EXISTS idx_qr_objetivo_estado
    ON "Qr" (objetivo, estado);

-- Índice compuesto para consultas por sesión + estado + objetivo
CREATE INDEX IF NOT EXISTS idx_qr_sesion_estado_objetivo
    ON "Qr" (sesion_id, estado, objetivo);

-- =============================================================
-- Tabla: Asistencia
-- =============================================================
-- Llaves foráneas (optimizan JOINs, subconsultas y validaciones de FK)
-- Usada en: obtenerAsistenciaPorId (JOIN "Sesion"), obtenerAsistenciasDeSesion,
--          existeAsistenciasEnSesion, marcarAsistenciasCerradasPorSistema,
--          subconsultas de conteo en SesionRepository.
CREATE INDEX IF NOT EXISTS idx_asistencia_sesion_id
    ON "Asistencia" (sesion_id);

-- Usada en: obtenerAsistenciaParaEstudianteEnSesionDatoEstado,
--          cerrarAsistenciaParaEstudiante,
--          obtenerEstadisticasAsistenciaParaEstudiante.
CREATE INDEX IF NOT EXISTS idx_asistencia_estudiante_id
    ON "Asistencia" (estudiante_id);

-- Usada en: obtenerAsistenciaParaEncargadoEnSesionDatoEstado,
--          cerrarAsistenciaParaEncargado,
--          obtenerEstadisticasAsistenciaParaEncargado.
CREATE INDEX IF NOT EXISTS idx_asistencia_encargado_id
    ON "Asistencia" (encargado_id);

-- Usadas en: existeAsistenciasConQr (WHERE qr_entrada_id = ? OR qr_salida_id = ?)
--            y subconsulta cantidad_asistencias en QrRepository.
CREATE INDEX IF NOT EXISTS idx_asistencia_qr_entrada_id
    ON "Asistencia" (qr_entrada_id);

CREATE INDEX IF NOT EXISTS idx_asistencia_qr_salida_id
    ON "Asistencia" (qr_salida_id);

-- Índices compuestos para las consultas más frecuentes:
--   obtenerAsistenciaParaEstudianteEnSesionDatoEstado
--   (WHERE sesion_id = ? AND estudiante_id = ? AND estado = ? ORDER BY id DESC)
CREATE INDEX IF NOT EXISTS idx_asistencia_sesion_estudiante_estado
    ON "Asistencia" (sesion_id, estudiante_id, estado);

--   obtenerAsistenciaParaEncargadoEnSesionDatoEstado
--   (WHERE sesion_id = ? AND encargado_id = ? AND estado = ? ORDER BY id DESC)
CREATE INDEX IF NOT EXISTS idx_asistencia_sesion_encargado_estado
    ON "Asistencia" (sesion_id, encargado_id, estado);

--   Subconsultas de conteo en SesionRepository
--   (WHERE sesion_id = ? AND estado = ?)
CREATE INDEX IF NOT EXISTS idx_asistencia_sesion_estado
    ON "Asistencia" (sesion_id, estado);
