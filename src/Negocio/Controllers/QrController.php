<?php
namespace App\Negocio\Controllers;

use App\Negocio\Services\QrService;
use App\Shared\Utils\RequestUtils;
use App\Negocio\Exceptions\BadRequestException;
use App\Shared\Enums\RolEnum;
use Throwable;

class QrController {
    public function __construct(
        private QrService $qrService
    ) {}

    /**
     * POST /api/qr
     * Cuerpo: { "objetivo": "estudiante" }
     */
    public function crearQR() {
        try {
            $body = RequestUtils::getJsonBody();
            $objetivo = isset($body['objetivo']) ? strtolower(trim($body['objetivo'])) : null;

            if (!$objetivo) {
                throw new BadRequestException("El campo 'objetivo' es obligatorio (estudiante/encargado).");
            }
            if (!in_array($objetivo, [RolEnum::ESTUDIANTE->value, RolEnum::ENCARGADO->value])) {
                throw new BadRequestException("El campo 'objetivo' debe ser 'estudiante' o 'encargado'.");
            }

            $qr = $this->qrService->crearQR($objetivo);

            return RequestUtils::sendResponse($qr, 201);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function obtenerQR(int $id) {
        try {
            $qr = $this->qrService->obtenerQRPorId($id);
            return RequestUtils::sendResponse($qr);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function inhabilitarQR(int $id) {
        try {
            $qr = $this->qrService->inhabilitarQR($id);
            return RequestUtils::sendResponse($qr);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function eliminarQR(int $id) {
        try {
            $this->qrService->eliminarQR($id);
            return RequestUtils::sendResponse([
                "message" => "QR eliminado o inhabilitado correctamente según su historial."
            ]);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
}