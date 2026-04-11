<?php
namespace App\Datos\Repository;

use App\Datos\Models\Asistencia;
use App\Datos\Models\Qr;
use App\Datos\Models\Sesion;
use App\Shared\Enums\AsistenciaEstadoEnum;
use App\Shared\Enums\EstadoGeneralEnum;
use App\Shared\Enums\SesionEstadoEnum;
use PDO;

class SesionRepository {
    public function __construct(private PDO $db){}

    public function listarSesiones(): array {
        $stmt = $this->db->query('SELECT * FROM "Sesion" ORDER BY id ASC');
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->mapearASesion($row), $results);
    }
    public function buscarPorId(int $id): ?Sesion {
        $stmt = $this->db->prepare('SELECT * FROM "Sesion" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapearASesion($row) : null;
    }
   
    public function aperturarSesion(Sesion $sesion,int $encargadoAperturaId): int {
        $query = 'INSERT INTO "Sesion" (fecha_apertura, observaciones, encargado_apertura_id) VALUES (:fecha_apertura, :observaciones, :encargado_apertura_id)';

        $stmt = $this->db->prepare($query);

        $stmt->execute([
            'fecha_apertura' => $sesion->fecha_apertura,
            'observaciones' => $sesion->observaciones,
            'encargado_apertura_id' => $encargadoAperturaId,
        ]);
        return (int) $this->db->lastInsertId();
    }
    public function cerrarSesion(int $id,Sesion $sesion,int $encargadoCierreId): bool {
        $query = 'UPDATE "Sesion" 
        SET fecha_cierre = :fecha_cierre, estado = :estado, observaciones = :observaciones,
         encargado_cierre_id = :encargado_cierre_id WHERE id = :id';

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id' => $id,
            'fecha_cierre' => $sesion->fecha_cierre,
            'estado' => SesionEstadoEnum::CERRADA->value,
            'observaciones' => $sesion->observaciones,
            'encargado_cierre_id' => $encargadoCierreId
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

    public function obtenerUltimaSesionDadoEstado(string $estado): ?Sesion {

        $stmt = $this->db->prepare('SELECT * FROM "Sesion" WHERE estado = :estado ORDER BY id DESC LIMIT 1');
        $stmt->execute(['estado' => $estado]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapearASesion($row) : null;
    }
    public function eliminarSesion(int $id): bool {
        $query = 'DELETE FROM "Sesion" WHERE id = :id';
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }
    
    
    private function mapearASesion(array $data): Sesion{
        return new Sesion(
            id: $data['id'],
            fecha_apertura: $data['fecha_apertura'],
            fecha_cierre: $data['fecha_cierre'],
            estado: $data['estado'],
            observaciones: $data['observaciones'],
            encargado_apertura_id: $data['encargado_apertura_id'],
            encargado_cierre_id: $data['encargado_cierre_id']
        );

    }
    
}