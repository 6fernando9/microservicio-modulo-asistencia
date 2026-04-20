<?php
namespace App\Negocio\Controllers;

use App\Negocio\Services\SesionService;
use App\Negocio\Dtos\Sesion\SesionUpdateDTO;
use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Exceptions\NotFoundException;
use App\Shared\Utils\RequestUtils;
use Throwable;

class SesionController {
    
    public function __construct(
        private SesionService $sesionService
    ) {}

    public function listarSesiones() {
        try {
            $data = $this->sesionService->listarSesiones();
            return RequestUtils::sendResponse($data);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function buscarSesionPorId(int $id) {
        try {
            $sesion = $this->sesionService->buscarSesionPorId($id);
            return RequestUtils::sendResponse($sesion);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function aperturarSesion() {
        try {
            $datos = RequestUtils::getJsonBody();
            
            // Mapeamos el DTO
            
            $observaciones = $datos['observaciones'] ?? null;
            

            $sesion = $this->sesionService->aperturarSesion($observaciones);
            
            return RequestUtils::sendResponse($sesion, 201);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function cerrarSesion(int $id) {
        try {
            $datos = RequestUtils::getJsonBody();
            
            $observaciones = $datos['observaciones'] ?? null;

            $sesion = $this->sesionService->cerrarSesion($id, $observaciones);
            
            return RequestUtils::sendResponse($sesion,200);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function eliminarSesion(int $id){
        try {
            $this->sesionService->eliminarSesion($id);
            
            return RequestUtils::sendResponse(['message' => 'Sesión con id ' . $id . ' eliminada correctamente.']);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function actualizarSesion(int $id) {
        try {
            $datos = RequestUtils::getJsonBody();

            $resultado = SesionUpdateDTO::fromArray($datos);
            if(!$resultado->esExitoso()) {
                throw new BadRequestException("Datos inválidos: " . $resultado->getError() );
            }
            $sesion = $this->sesionService->actualizarSesion($id, $resultado->getValor());
            
            return RequestUtils::sendResponse($sesion,200);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function obtenerAsistenciasDeSesion(int $sesionId) {
        try {
            $asistencias = $this->sesionService->obtenerAsistenciasDeSesion($sesionId);
            return RequestUtils::sendResponse($asistencias);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function obtenerQrsDeSesion(int $sesionId) {
        try {
            $qr = $this->sesionService->obtenerQrsDeSesion($sesionId);
            return RequestUtils::sendResponse($qr);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function obtenerSolicitudesDeSesion(int $sesionId) {
        try {
            $solicitudes = $this->sesionService->obtenerSolicitudesDeSesion($sesionId);
            return RequestUtils::sendResponse($solicitudes);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

    public function obtenerSesionActiva() {
        try {
            $sesionActiva = $this->sesionService->obtenerSesionActiva();
            if (!$sesionActiva) {
                throw new NotFoundException("No hay una sesión activa en este momento.");
            }
            return RequestUtils::sendResponse($sesionActiva);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function obtenerSesionActivaSimple() {
        try {
            $sesionActiva = $this->sesionService->obtenerSesionActivaSimple();
            if (!$sesionActiva) {
                throw new NotFoundException("No hay una sesión activa en este momento.");
            }
            return RequestUtils::sendResponse($sesionActiva);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }
    public function buscarSesionPorIdSimple(int $id) {
        try {
            $sesion = $this->sesionService->buscarSesionPorIdSimple($id);
            return RequestUtils::sendResponse($sesion);
        } catch (Throwable $e) {
            return RequestUtils::handleError($e);
        }
    }

}