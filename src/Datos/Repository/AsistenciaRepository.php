<?php
namespace App\Datos\Repository;

use App\Datos\Models\Asistencia;
use App\Shared\Enums\AsistenciaEstadoEnum;
use PDO;

class AsistenciaRepository{
    public function __construct(
        private PDO $db
    ){}

    public function obtenerAsistenciaPorId(int $id): ?Asistencia {
        $stmt = $this->db->prepare('SELECT * FROM "Asistencia" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Asistencia(
            id: $row['id'],
            fecha_llegada: $row['fecha_llegada'],
            fecha_salida: $row['fecha_salida'],
            estado: $row['estado'],
            observaciones: $row['observaciones'],
            encargado_id: $row['encargado_id'],
            estudiante_id: $row['estudiante_id'],
            sesion_id: $row['sesion_id'],
            sesion: null
        ) : null;
    }
    public function crearAsistenciaParaEstudiante(Asistencia $asistencia,int $sesionId,int $estudianteId): int {
        
        
        $query = 'INSERT INTO "Asistencia" (sesion_id, estudiante_id,fecha_llegada) 
        VALUES (:sesion_id, :estudiante_id, :fecha_llegada)';
        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'fecha_llegada' => $asistencia->fecha_llegada
        ]);

        return (int) $this->db->lastInsertId();
    }
    public function cerrarAsistenciaParaEstudiante(Asistencia $asistencia, int $sesionId, int $estudianteId): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_salida = :fecha_salida, estado = :estado, 
                 estudiante_id = :estudiante_id 
                 WHERE sesion_id = :sesion_id AND estudiante_id = :estudiante_id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'fecha_salida' => $asistencia->fecha_salida,
            'estado' => AsistenciaEstadoEnum::FINALIZADO->value,
        ]);
    }
     public function crearAsistenciaParaEncargado(Asistencia $asistencia,int $sesionId,int $encargadoId): int {
        
        
        $query = 'INSERT INTO "Asistencia" (sesion_id, encargado_id,fecha_llegada,observaciones) 
        VALUES (:sesion_id, :encargado_id, :fecha_llegada,:observaciones)';
        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'sesion_id' => $sesionId,
            'encargado_id' => $encargadoId,
            'fecha_llegada' => $asistencia->fecha_llegada,
            'observaciones' => $asistencia->observaciones
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function cerrarAsistenciaParaEncargado(Asistencia $asistencia, int $sesionId, int $encargadoId): bool {
        $query = 'UPDATE "Asistencia" 
                 SET fecha_salida = :fecha_salida, estado = :estado, 
                 observaciones = :observaciones, encargado_id = :encargado_id 
                 WHERE sesion_id = :sesion_id AND encargado_id = :encargado_id';
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'sesion_id' => $sesionId,
            'encargado_id' => $encargadoId,
            'fecha_salida' => $asistencia->fecha_salida,
            'estado' => AsistenciaEstadoEnum::FINALIZADO->value,
            'observaciones' => $asistencia->observaciones
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

}