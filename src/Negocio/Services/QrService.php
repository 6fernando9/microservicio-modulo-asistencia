<?php
namespace App\Negocio\Services;

use App\Datos\Models\Qr;
use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\QrRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Exceptions\BadRequestException;
use App\Shared\Enums\EstadoGeneralEnum;
use App\Shared\Enums\SesionEstadoEnum;
use Throwable;

class QrService {
    public function __construct(
        private QrRepository $qrRepository,
        private SesionRepository $sesionRepository,
        private AsistenciaRepository $asistenciaRepository
    ) {}

    public function crearQR(string $objetivo): Qr {
    
        $sesionAbierta = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        if (!$sesionAbierta) {
            throw new BadRequestException("No se puede crear un QR si no hay una sesión abierta.");
        }
        $token = bin2hex(random_bytes(16));
        $qr = new Qr(
            id: null,
            token: $token,
            objetivo: $objetivo,
            estado: EstadoGeneralEnum::ACTIVO->value,
            sesion_id: $sesionAbierta->id
        );

        $id = $this->qrRepository->crearQR($qr, $sesionAbierta->id);
        
        if (!$id) {
            throw new BadRequestException("Error interno al intentar guardar el QR.");
        }
        $qrAActualizar = new Qr(
            id: null,
            token: null,
            objetivo: $objetivo,
            estado: EstadoGeneralEnum::INACTIVO->value,
            sesion_id: $sesionAbierta->id
        );
        $this->qrRepository->cambiarEstadoQrsActivos($id, $qrAActualizar, $sesionAbierta->id);
        $qr->id = $id;
        return $qr;
    }
    public function obtenerQRPorId(int $id): Qr {
        $qr = $this->qrRepository->buscarPorId($id);
        if (!$qr) {
            throw new BadRequestException("QR no encontrado con ID: $id");
        }
        return $qr;
    }
    
    public function inhabilitarQR(int $id): QR {
        $exito = $this->qrRepository->cambiarEstadoQR($id, EstadoGeneralEnum::INACTIVO->value);
        if (!$exito) {
            throw new BadRequestException("Error interno al intentar inhabilitar el QR con ID $id.");
        }
        return $this->obtenerQRPorId($id);
    }


    public function eliminarQR(int $id): void {
        if ($this->asistenciaRepository->existeAsistenciasConQr($id)) {
            $exito = $this->inhabilitarQR($id);
            if (!$exito) {
                throw new BadRequestException("Error interno al intentar inhabilitar el QR con ID $id antes de eliminarlo.");
            }
            return;
        }

        $exito = $this->qrRepository->eliminarQR($id);
        if (!$exito) {
            throw new BadRequestException("Error interno al intentar eliminar el QR con ID $id.");
        }
    }
}