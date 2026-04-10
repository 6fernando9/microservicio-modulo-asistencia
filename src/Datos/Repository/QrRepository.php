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
        $stmt = $this->db->prepare('SELECT * FROM "Qr" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        return new Qr(
            id: $data['id'],
            token: $data['token'],
            estado: $data['estado'],
            objetivo: $data['objetivo'],
            sesion_id: $data['sesion_id']
        );
    }
    public function crearQR(Qr $dto,int $sesionId): int {
        $stmt = $this->db->prepare('INSERT INTO "Qr" (token, estado, objetivo, sesion_id) VALUES (:token, :estado, :objetivo, :sesion_id)');
        $stmt->execute([
            'token' => $dto->token,
            'estado' => EstadoGeneralEnum::ACTIVO->value,
            'objetivo' => $dto->objetivo,
            'sesion_id' => $sesionId
        ]);
        $id = $this->db->lastInsertId();
        return $id;
    }
    public function cambiarEstadoQR(int $id, string $estado){
        $stmt = $this->db->prepare('UPDATE "Qr" SET estado = :estado WHERE id = :id');
        $stmt->execute([
            'estado' => $estado,
            'id' => $id
        ]);
    }
    public function cambiarEstadoQrsActivos(int $qrId,Qr $qr,int $sesionId){
        $stmt = $this->db->prepare('UPDATE "Qr" SET estado = :estado WHERE estado = :estadoActivo AND id != :id AND objetivo = :objetivo AND sesion_id = :sesion_id' );
        $stmt->execute([
            'estado' => $qr->estado,
            'estadoActivo' => EstadoGeneralEnum::ACTIVO->value,
            'id' => $qrId,
            'objetivo' => $qr->objetivo,
            'sesion_id' => $sesionId
        ]);
    }

    public function existeAsistenciasConQr(int $qrId): bool {
        $query = 'SELECT EXISTS(SELECT 1 FROM "Asistencia" WHERE qr_entrada_id = :qrId OR qr_salida_id = :qrId)';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['qrId' => $qrId]);
        return $stmt->fetchColumn();
    }
    public function eliminarQR(int $id){
        $stmt = $this->db->prepare('DELETE FROM "Qr" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    public function existeQrConMismoObjetivoActivo(string $objetivo): bool {
        $estadoActivo = EstadoGeneralEnum::ACTIVO->value;
        $query = 'SELECT EXISTS(SELECT 1 FROM "Qr" WHERE objetivo = :objetivo AND estado = :estado)';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['objetivo' => $objetivo, 'estado' => $estadoActivo]);
        return $stmt->fetchColumn();
    }
}