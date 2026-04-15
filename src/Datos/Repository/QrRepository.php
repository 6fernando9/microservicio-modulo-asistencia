<?php
namespace App\Datos\Repository;

use App\Datos\Models\Qr;
use App\Shared\Enums\EstadoGeneralEnum;
use PDO;

class QrRepository{
    public function __construct(
        private PDO $db
    ){}
    public function buscarPorId(int $id): ?Qr {
        $query = 'SELECT q.*,
        (SELECT COUNT(*) 
        FROM "Asistencia" a 
        WHERE a.qr_entrada_id = q.id OR a.qr_salida_id = q.id) as cantidad_asistencias
                FROM "Qr" q
                WHERE q.id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapearQr($data) : null;
    }
    public function crearQR(Qr $dto,int $sesionId): int {
        $query = 'INSERT INTO "Qr" 
        (token, estado, objetivo, sesion_id) 
        VALUES (:token, :estado, :objetivo, :sesion_id)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'token' => $dto->token,
            'estado' => EstadoGeneralEnum::ACTIVO->value,
            'objetivo' => $dto->objetivo,
            'sesion_id' => $sesionId
        ]);
        $id = $this->db->lastInsertId();
        return $id;
    }
    public function cambiarEstadoQR(int $id, string $estado):bool{
        $stmt = $this->db->prepare('UPDATE "Qr" SET estado = :estado WHERE id = :id');
        return $stmt->execute([
            'estado' => $estado,
            'id' => $id
        ]);

    }
    public function cambiarEstadoQrsActivos(int $qrId,Qr $qr,int $sesionId):bool{
        $query = 'UPDATE "Qr" 
        SET estado = :estado 
        WHERE estado = :estadoActivo AND id != :id 
        AND objetivo = :objetivo AND sesion_id = :sesion_id';
        $stmt = $this->db->prepare($query) ;
        return $stmt->execute([
            'estado' => $qr->estado,
            'estadoActivo' => EstadoGeneralEnum::ACTIVO->value,
            'id' => $qrId,
            'objetivo' => $qr->objetivo,
            'sesion_id' => $sesionId
        ]);
    }

    public function eliminarQR(int $id){
        $stmt = $this->db->prepare('DELETE FROM "Qr" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
    public function existeQrConMismoObjetivoActivo(string $objetivo): bool {
        $estadoActivo = EstadoGeneralEnum::ACTIVO->value;
        $query = 'SELECT EXISTS(SELECT 1 FROM "Qr" WHERE objetivo = :objetivo AND estado = :estado)';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['objetivo' => $objetivo, 'estado' => $estadoActivo]);
        return $stmt->fetchColumn();
    }

    public function obtenerQrsDeSesion(int $id): array {
        $query = 'SELECT q.*, 
            (SELECT COUNT(*) 
            FROM "Asistencia" a 
            WHERE a.qr_entrada_id = q.id OR a.qr_salida_id = q.id) as cantidad_asistencias
                    FROM "Qr" q
                    WHERE q.sesion_id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->mapearQr($row), $rows);
    }
    public function obtenerQRDadoToken(string $token): ?Qr {
        $query = 'SELECT q.*, 
            (SELECT COUNT(*) 
            FROM "Asistencia" a 
            WHERE a.qr_entrada_id = q.id OR a.qr_salida_id = q.id) as cantidad_asistencias
                    FROM "Qr" q
                    WHERE q.token = :token ORDER BY q.id DESC LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'token' => $token
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
        return $row ? $this->mapearQr($row) : null;
    }
    public function marcarQrsInactivosPorSistema(int $sesionId): bool {
        $query = 'UPDATE "Qr" 
        SET estado = :estado
        WHERE sesion_id = :sesion_id AND estado = :estadoActivo';
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'sesion_id' => $sesionId,
            'estado' => EstadoGeneralEnum::INACTIVO->value,
            'estadoActivo' => EstadoGeneralEnum::ACTIVO->value
        ]);
    }
    public function existeQrEnSesion(int $sesionId): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM "Qr" WHERE sesion_id = :sesion_id');
        $stmt->execute(['sesion_id' => $sesionId]);
        return (int) $stmt->fetchColumn() > 0;
    }
    public function obtenerQrsActivosDeSesion(int $sesionId, array $objetivos): array {
        
        $placeholders = implode(',', array_fill(0, count($objetivos), '?'));
        
        $query = 'SELECT q.*, 
            (SELECT COUNT(*) 
            FROM "Asistencia" a 
            WHERE a.qr_entrada_id = q.id OR a.qr_salida_id = q.id) as cantidad_asistencias
                    FROM "Qr" q
                    WHERE q.sesion_id = ? 
                    AND q.estado = ? 
                    AND q.objetivo IN (' . $placeholders . ')
                    ORDER BY q.id DESC';

        $stmt = $this->db->prepare($query);
        
        
        $params = array_merge(
            [$sesionId, EstadoGeneralEnum::ACTIVO->value], 
            $objetivos
        );
        
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        return array_map(fn($row) => $this->mapearQr($row), $rows);
    }
    private function mapearQr(array $data): Qr {
        return new Qr(
            id: $data['id'],
            token: $data['token'],
            estado: $data['estado'],
            objetivo: $data['objetivo'],
            sesion_id: $data['sesion_id'],
            cantidad_asistencias: $data['cantidad_asistencias'] ?? 0
        );
    }
}