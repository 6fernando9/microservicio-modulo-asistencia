<?php
namespace App\Negocio\Controllers;

use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Services\AsistenciaService;
use App\Shared\Utils\RequestUtils;
use Throwable;

class AsistenciaController {
    
    public function __construct(
        private AsistenciaService $asistenciaService
    ) {}

    public function obtenerAsistenciaPorId(int $id) {
        try {
            $asistencia = $this->asistenciaService->obtenerAsistenciaPorId($id);
            return RequestUtils::sendResponse($asistencia);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function crearAsistencia() {
        try {
            $datos = RequestUtils::getJsonBody();
            $observaciones = $datos['observaciones'] ?? null;
            $token = $datos['token_qr'] ?? null;
            if (!$token) {
                throw new BadRequestException("El campo 'token_qr' es obligatorio para crear una asistencia.");
            }
            $asistencia = $this->asistenciaService->crearAsistencia($observaciones, $token);
            
            return RequestUtils::sendResponse($asistencia, 201);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function finalizarAsistencia() {
        try {
            $datos = RequestUtils::getJsonBody();
            $observaciones = $datos['observaciones'] ?? null;
            $token = $datos['token_qr'] ?? null;
            if (!$token) {
                throw new BadRequestException("El campo 'token_qr' es obligatorio para crear una asistencia.");
            }
            $asistencia = $this->asistenciaService->finalizarAsistencia($observaciones, $token);
            
            return RequestUtils::sendResponse($asistencia, 200);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function actualizarAsistencia(int $id) {
        try {
            $datos = RequestUtils::getJsonBody();
            $observaciones = $datos['observaciones'] ?? null;

            $asistencia = $this->asistenciaService->actualizarAsistencia($id, $observaciones);
            
            return RequestUtils::sendResponse($asistencia, 200);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function verificarTokenQR(){
        try {
            $datos = RequestUtils::getJsonBody();
            $token = $datos['token_qr'] ?? null;
            if (!$token) {
                throw new BadRequestException("El campo 'token_qr' es obligatorio para verificar el token.");
            }
            $resultado = $this->asistenciaService->verificarToken($token);
            
            return RequestUtils::sendResponse($resultado, 200);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
}