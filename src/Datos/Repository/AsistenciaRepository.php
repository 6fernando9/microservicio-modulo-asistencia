<?php
namespace App\Datos\Repository;

use App\Datos\Models\Asistencia;
use App\Datos\Models\Qr;
use App\Datos\Models\Sesion;
use App\Shared\Enums\AsistenciaEstadoEnum;
use PDO;

class AsistenciaRepository{
    public function __construct(
        private PDO $db
    ){}

    public function obtenerAsistenciaPorId(int $id): ?Asistencia {
        $query = 'SELECT a.*, s.fecha_apertura, s.fecha_cierre, 
            s.estado AS estado_sesion, s.encargado_apertura_id, 
            s.encargado_cierre_id, s.id AS sesion_id, 
            s.observaciones AS observaciones_sesion
            FROM "Asistencia" a 
            JOIN "Sesion" s ON a.sesion_id = s.id 
            WHERE a.id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapearAsistenciaConSesion($row) : null;
    }
    public function crearAsistenciaParaEstudiante(Asistencia $asistencia,int $sesionId,int $estudianteId,int $qrEntradaId): int {
        
        $query = 'INSERT INTO "Asistencia" (sesion_id, estudiante_id,fecha_llegada, qr_entrada_id) 
        VALUES (:sesion_id, :estudiante_id, :fecha_llegada, :qr_entrada_id)';
        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'fecha_llegada' => $asistencia->fecha_llegada,
            'qr_entrada_id' => $qrEntradaId
        ]);

        return (int) $this->db->lastInsertId();
    }
    public function crearAsistenciaParaEncargado(Asistencia $asistencia,int $sesionId,int $encargadoId,int $qrEntradaId): int {
        
        
        $query = 'INSERT INTO "Asistencia" (sesion_id, encargado_id,fecha_llegada,observaciones, qr_entrada_id) 
        VALUES (:sesion_id, :encargado_id, :fecha_llegada,:observaciones, :qr_entrada_id)';
        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'sesion_id' => $sesionId,
            'encargado_id' => $encargadoId,
            'fecha_llegada' => $asistencia->fecha_llegada,
            'observaciones' => $asistencia->observaciones,
            'qr_entrada_id' => $qrEntradaId
        ]);

        return (int) $this->db->lastInsertId();
    }


    public function cerrarAsistenciaParaEstudiante(Asistencia $asistencia, int $sesionId, int $estudianteId,int $qrSalidaId): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_salida = :fecha_salida, estado = :estado, 
                 estudiante_id = :estudiante_id, qr_salida_id = :qr_salida_id
                 WHERE sesion_id = :sesion_id AND estudiante_id = :estudiante_id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'fecha_salida' => $asistencia->fecha_salida,
            'estado' => AsistenciaEstadoEnum::FINALIZADO->value,
            'qr_salida_id' => $qrSalidaId
        ]);
    }
     
    public function cerrarAsistenciaParaEncargado(Asistencia $asistencia, int $sesionId, int $encargadoId,int $qrSalidaId): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_salida = :fecha_salida, estado = :estado, 
                 observaciones = :observaciones, encargado_id = :encargado_id,
                    qr_salida_id = :qr_salida_id
                 WHERE sesion_id = :sesion_id AND encargado_id = :encargado_id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'sesion_id' => $sesionId,
            'encargado_id' => $encargadoId,
            'fecha_salida' => $asistencia->fecha_salida,
            'estado' => AsistenciaEstadoEnum::FINALIZADO->value,
            'observaciones' => $asistencia->observaciones,
            'qr_salida_id' => $qrSalidaId
        ]);
    }
    public function actualizarAsistencia(int $id, Asistencia $asistencia): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_llegada = :fecha_llegada, 
                 fecha_salida = :fecha_salida,
                 observaciones = :observaciones
                 WHERE id = :id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'fecha_llegada' => $asistencia->fecha_llegada,
            'fecha_salida' => $asistencia->fecha_salida,
            'observaciones' => $asistencia->observaciones,
        ]);
    }
    public function actualizarAsistenciaObservaciones(int $id, ?string $observaciones): bool {
        $query = 'UPDATE "Asistencia" 
                 SET observaciones = :observaciones
                 WHERE id = :id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'observaciones' => $observaciones,
        ]);
    }

    public function actualizarAsistenciaCompleta(int $id, Asistencia $asistencia, int $estudianteId,int $encargadoId): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_llegada = :fecha_llegada, 
                 fecha_salida = :fecha_salida,
                 estado = :estado,
                 observaciones = :observaciones,
                estudiante_id = :estudiante_id,
                encargado_id = :encargado_id
                 WHERE id = :id AND estudiante_id = :estudiante_id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'fecha_llegada' => $asistencia->fecha_llegada,
            'fecha_salida' => $asistencia->fecha_salida,
            'estado' => $asistencia->estado,
            'observaciones' => $asistencia->observaciones,
            'estudiante_id' => $estudianteId,
            'encargado_id' => $encargadoId
        ]);

    }
    public function cancelarAsistencia(int $id): bool {
        $query = 'UPDATE "Asistencia" SET estado = :estado WHERE id = :id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'estado' => AsistenciaEstadoEnum::CANCELADO->value
        ]);
    }
   
    public function obtenerAsistenciaParaEncargadoEnSesionDatoEstado(int $sesionId, int $encargadoId, string $estado): ?Asistencia {
        $query = 'SELECT a.*, s.fecha_apertura, s.fecha_cierre, s.estado as estado_sesion, 
                     s.encargado_apertura_id, s.encargado_cierre_id, 
                     s.observaciones as observaciones_sesion 
              FROM "Asistencia" a
              JOIN "Sesion" s ON a.sesion_id = s.id
              WHERE a.sesion_id = :sesion_id AND a.encargado_id = :encargado_id 
              AND a.estado = :estado ORDER BY a.id DESC LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'sesion_id' => $sesionId,
            'encargado_id' => $encargadoId,
            'estado' => $estado
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapearAsistencia($row) : null;
    }
    public function obtenerAsistenciaParaEstudianteEnSesionDatoEstado(int $sesionId, int $estudianteId, string $estado): ?Asistencia {
        $query = 'SELECT a.*, s.fecha_apertura, s.fecha_cierre, s.estado as estado_sesion, 
                     s.encargado_apertura_id, s.encargado_cierre_id, 
                     s.observaciones as observaciones_sesion 
              FROM "Asistencia" a
              JOIN "Sesion" s ON a.sesion_id = s.id
              WHERE a.sesion_id = :sesion_id AND a.estudiante_id = :estudiante_id 
              AND a.estado = :estado ORDER BY a.id DESC LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'estado' => $estado
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->mapearAsistencia($row) : null;
    }
    public function obtenerEstadisticasAsistenciaParaEstudiante(int $sesionId,int $estudianteId): array {
        $sql = 'SELECT 
                    COUNT(*) as total_historico,
                    COUNT(*) FILTER (WHERE sesion_id = :sesion_id) as total_sesion
                FROM "Asistencia" 
                WHERE estudiante_id = :estudiante_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'estudiante_id' => $estudianteId,
            'sesion_id' => $sesionId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_historico' => (int)($result['total_historico'] ?? 0),
            'total_sesion'    => (int)($result['total_sesion'] ?? 0)
        ];

    }
    public function obtenerEstadisticasAsistenciaParaEncargado(int $sesionId,int $encargadoId ): array {
        $sql = 'SELECT 
                    COUNT(*) as total_historico,
                    COUNT(*) FILTER (WHERE sesion_id = :sesion_id) as total_sesion
                FROM "Asistencia" 
                WHERE encargado_id = :encargado_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'encargado_id' => $encargadoId,
            'sesion_id' => $sesionId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_historico' => (int)($result['total_historico'] ?? 0),
            'total_sesion'    => (int)($result['total_sesion'] ?? 0)
        ];

    }
    private function mapearAsistenciaConSesion(array $data): Asistencia{
        return new Asistencia(
            id: $data['id'],
            fecha_llegada: $data['fecha_llegada'],
            fecha_salida: $data['fecha_salida'],
            estado: $data['estado'],
            observaciones: $data['observaciones'],
            encargado_id: $data['encargado_id'],
            estudiante_id: $data['estudiante_id'],
            sesion_id: $data['sesion_id'],
            es_cerrado_por_sistema: $data['es_cerrado_por_sistema'] === 't',
            qr_entrada_id: $data['qr_entrada_id'],
            qr_salida_id: $data['qr_salida_id'],
            sesion: new Sesion(
                id: $data['sesion_id'],
                fecha_apertura: $data['fecha_apertura'],
                fecha_cierre: $data['fecha_cierre'],
                estado: $data['estado_sesion'],
                encargado_apertura_id: $data['encargado_apertura_id'],
                encargado_cierre_id: $data['encargado_cierre_id'],
                observaciones: $data['observaciones_sesion']
            )
        );

    }
    private function mapearAsistencia(array $data): Asistencia{
        return new Asistencia(
            id: $data['id'],
            fecha_llegada: $data['fecha_llegada'],
            fecha_salida: $data['fecha_salida'],
            estado: $data['estado'],
            observaciones: $data['observaciones'],
            encargado_id: $data['encargado_id'],
            estudiante_id: $data['estudiante_id'],
            sesion_id: $data['sesion_id'],
            es_cerrado_por_sistema: $data['es_cerrado_por_sistema'] === 't'
        );

    }
    


    public function obtenerAsistenciasDeSesion(int $sesionId): array {
        
        $query = 'SELECT a.*
              FROM "Asistencia" a 
              WHERE a.sesion_id = :sesionId';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['sesionId' => $sesionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->mapearAsistencia($row), $rows);
        
    }
    
     public function existeAsistenciasConQr(int $qrId): bool {
        $query = 'SELECT EXISTS(SELECT 1 FROM "Asistencia" WHERE qr_entrada_id = :qrId OR qr_salida_id = :qrId)';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['qrId' => $qrId]);
        return $stmt->fetchColumn();
    }
    public function marcarAsistenciasCerradasPorSistema(int $sesionId): bool {
        
        $query = 'UPDATE "Asistencia" 
        SET estado = :estado, es_cerrado_por_sistema = true, fecha_salida = :fecha_salida
        WHERE sesion_id = :sesion_id AND fecha_salida IS NULL';
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'sesion_id' => $sesionId,
            'estado' => AsistenciaEstadoEnum::FINALIZADO->value,
            'fecha_salida' => date('Y-m-d H:i:s')
        ]);
    }
    public function existeAsistenciasEnSesion(int $id): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM "Asistencia" WHERE sesion_id = :sesion_id');
        $stmt->execute(['sesion_id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
    
}