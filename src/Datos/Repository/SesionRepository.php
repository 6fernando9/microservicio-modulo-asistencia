<?php
namespace App\Datos\Repository;

use App\Datos\Models\Asistencia;
use App\Datos\Models\Sesion;
use App\Shared\Enums\SesionEstadoEnum;
use PDO;

class SesionRepository {
    public function __construct(private PDO $db){}

    public function listarSesiones(): array {
        $stmt = $this->db->query('SELECT * FROM "Sesion" ORDER BY id ASC');
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Sesion(
                id:$row['id'],
                fecha_apertura:$row['fecha_apertura'],
                fecha_cierre:$row['fecha_cierre'],
                estado:$row['estado'],
                observaciones:$row['observaciones'],
                encargado_apertura_id:$row['encargado_apertura_id'],
                encargado_cierre_id:$row['encargado_cierre_id']
            ), $results);
    }
    public function buscarPorId(int $id): ?Sesion {
        $stmt = $this->db->prepare('SELECT * FROM "Sesion" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Sesion(
                id:$row['id'],
                fecha_apertura:$row['fecha_apertura'],
                fecha_cierre:$row['fecha_cierre'],
                estado:$row['estado'],
                observaciones:$row['observaciones'],
                encargado_apertura_id:$row['encargado_apertura_id'],
                encargado_cierre_id:$row['encargado_cierre_id']
            ) : null;
    }
   
    public function aperturarSesion(Sesion $sesion): int {
        $query = 'INSERT INTO "Sesion" (fecha_apertura, observaciones, encargado_apertura_id) VALUES (:fecha_apertura, :observaciones, :encargado_apertura_id)';

        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'fecha_apertura' => $sesion->fecha_apertura,
            'observaciones' => $sesion->observaciones,
            'encargado_apertura_id' => $sesion->encargado_apertura_id,
        ]);
        return (int) $this->db->lastInsertId();
    }
    public function cerrarSesion(int $id, string $fecha_cierre, ?string $observaciones, int $encargado_cierre_id): bool {
        $query = 'UPDATE "Sesion" SET fecha_cierre = :fecha_cierre, estado = :estado, observaciones = :observaciones, encargado_cierre_id = :encargado_cierre_id WHERE id = :id';

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'fecha_cierre' => $fecha_cierre,
            'estado' => SesionEstadoEnum::CERRADA->value,
            'observaciones' => $observaciones,
            'encargado_cierre_id' => $encargado_cierre_id
        ]);
    }
    
    
    public function actualizarSesion(int $id, Sesion $sesion): bool {
        // Implementar lógica de actualización de sesión si es necesario
        #actualizamos solo fecha apertura,fecha cierre(opcional si viene),observaciones
        $query = 'UPDATE "Sesion" SET fecha_apertura = :fecha_apertura, fecha_cierre = :fecha_cierre, observaciones = :observaciones WHERE id = :id';
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'fecha_apertura' => $sesion->fecha_apertura,
            'fecha_cierre' => $sesion->fecha_cierre,
            'observaciones' => $sesion->observaciones
        ]);
        
    }

    #$quizas no se use, solo seria para encargado con permisos especificos
    public function actualizarSesionCompleta(int $id, Sesion $sesion,int $encargadoAperturaId,int $encargadoCierreId): bool{
        #aqui actualizamos todos los campos de la sesion
        $query = 'UPDATE "Sesion" SET fecha_apertura = :fecha_apertura, fecha_cierre = :fecha_cierre, estado = :estado, observaciones = :observaciones, encargado_apertura_id = :encargado_apertura_id, encargado_cierre_id = :encargado_cierre_id WHERE id = :id';
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'fecha_apertura' => $sesion->fecha_apertura,
            'fecha_cierre' => $sesion->fecha_cierre,
            'estado' => $sesion->estado,
            'observaciones' => $sesion->observaciones,
            'encargado_apertura_id' => $encargadoAperturaId,
            'encargado_cierre_id' => $encargadoCierreId
        ]);
    }

    public function existeAsistenciasEnSesion(int $id): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM "Asistencia" WHERE sesion_id = :sesion_id');
        $stmt->execute(['sesion_id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
    public function obtenerAsistenciasDeSesion(int $id): array {
        $stmt = $this->db->prepare('SELECT * FROM "Asistencia" WHERE sesion_id = :sesion_id');
        $stmt->execute(['sesion_id' => $id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Asistencia(
                id:$row['id'],
                fecha_llegada:$row['fecha_llegada'],
                fecha_salida:$row['fecha_salida'],
                estado:$row['estado'],
                observaciones:$row['observaciones'],
                encargado_id:$row['encargado_id'],
                estudiante_id:$row['estudiante_id'],
                sesion_id:$row['sesion_id'],
                sesion: $this->buscarPorId($row['sesion_id'])
            ), $results);
    }

    public function obtenerUltimaSesionDadoEstado(string $estado): ?Sesion {
        #$stmt = $this->db->query('SELECT * FROM "Sesion" WHERE estado = \'abierta\' ORDER BY id DESC LIMIT 1');
        $stmt = $this->db->prepare('SELECT * FROM "Sesion" WHERE estado = :estado ORDER BY id DESC LIMIT 1');
        $stmt->execute(['estado' => $estado]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Sesion(
                id:$row['id'],
                fecha_apertura:$row['fecha_apertura'],
                fecha_cierre:$row['fecha_cierre'],
                estado:$row['estado'],
                observaciones:$row['observaciones'],
                encargado_apertura_id:$row['encargado_apertura_id'],
                encargado_cierre_id:$row['encargado_cierre_id']
            ) : null;
    }
    public function eliminarSesion(int $id): bool {
        $query = 'DELETE FROM "Sesion" WHERE id = :id';
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }
    
}