<?php
namespace App\Negocio\Services;

use App\Datos\Config\Secrets;
use App\Datos\Models\Asistencia;
use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Exceptions\InternalServerException;
use App\Negocio\Exceptions\NotFoundException;
use App\Shared\Enums\AsistenciaEstadoEnum;
use App\Shared\Enums\EstadoGeneralEnum;
use App\Shared\Enums\RolEnum;
use App\Shared\Enums\SesionEstadoEnum;
use App\Shared\Utils\RequestUtils;

class AsistenciaService{
    public function __construct(
        private AsistenciaRepository $asistenciaRepository,
        private SesionRepository $sesionRepository
    ){}
    // public function obtenerAsistenciaPorId(int $id): Asistencia {
    //     $asistencia = $this->asistenciaRepository->obtenerAsistenciaPorId($id);
    //     if (!$asistencia) {
    //         throw new NotFoundException("La asistencia con ID $id no existe.");
    //     }
    //     return $asistencia;
    // }
    public function obtenerAsistenciaPorId(int $id): Asistencia {
        // 1. Buscar la asistencia en la BD local
        $asistencia = $this->asistenciaRepository->obtenerAsistenciaPorId($id);
        if (!$asistencia) {
            throw new NotFoundException("La asistencia con ID $id no existe.");
        }

        // 2. Identificar quién es el actor (Estudiante o Encargado)
        $idActor = $asistencia->estudiante_id ?? $asistencia->encargado_id;

        if ($idActor) {
            
            $url = Secrets::microservicioUsuariosURL() . "/api/usuario/obtener-usuarios";
            
            $respuesta = RequestUtils::fetch($url, 'POST', ['ids' => [$idActor]]);
            
            if (!empty($respuesta)) {
                $datosActor = $respuesta[0]; // Tomamos el primer (y único) resultado
                
                if ($asistencia->estudiante_id) {
                    $asistencia->estudiante = $datosActor;
                    $asistencia->encargado = null;
                } else {
                    $asistencia->encargado = $datosActor;
                    $asistencia->estudiante = null;
                }
            }
           
        }

        return $asistencia;
    }
    public function crearAsistencia(?string $observaciones,string $token) {
        // 1. Validar Sesión
        $sesionAbierta = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        if (!$sesionAbierta) {
            throw new BadRequestException("No hay una sesión abierta para registrar la asistencia.");
        }

        // 2. Obtener Usuario del Microservicio
        $url = Secrets::microservicioUsuariosURL() . "/api/auth/me";
        $dataUsuario = RequestUtils::fetch($url, 'GET');
        
        $usuarioId = $dataUsuario['id'];
        $rol = strtolower($dataUsuario['rol'] ?? 'desconocido');
        $fechaLlegada = date('Y-m-d H:i:s');

        // 3. Determinar campos según el Rol
        $esEstudiante = ($rol === RolEnum::ESTUDIANTE->value);
        $esEncargado = ($rol === RolEnum::ENCARGADO->value);
        if (!$esEstudiante && !$esEncargado) {
            throw new BadRequestException("El rol '$rol' no está autorizado para registrar asistencias.");
        }
        // 4. Verificación de existencia (Lógica Unificada)
        $asistenciaExistente = $esEstudiante 
            ? $this->asistenciaRepository->obtenerAsistenciaParaEstudianteEnSesionDatoEstado($sesionAbierta->id, $usuarioId, AsistenciaEstadoEnum::PRESENTE->value)
            : $this->asistenciaRepository->obtenerAsistenciaParaEncargadoEnSesionDatoEstado($sesionAbierta->id, $usuarioId, AsistenciaEstadoEnum::PRESENTE->value);
        
        if ($asistenciaExistente) {
            throw new BadRequestException("Ya tienes una asistencia registrada en la sesión actual.");
        }
  
        
        $qrValido = $this->asistenciaRepository->obtenerQRDadoEstadoYToken(EstadoGeneralEnum::ACTIVO->value, $token);
        if (!$qrValido || $qrValido->estado !== EstadoGeneralEnum::ACTIVO->value) {
            throw new BadRequestException("El token QR proporcionado no es válido o no está activo.");
        }

        $observaciones = $esEstudiante ? null : $observaciones;
        // 5. Preparar Objeto Asistencia
        $asistencia = new Asistencia(
            id: null,
            sesion_id: $sesionAbierta->id,
            fecha_llegada: $fechaLlegada,
            observaciones: $observaciones,
            fecha_salida: null,
            estado: AsistenciaEstadoEnum::PRESENTE->value,
            estudiante_id: $esEstudiante ? $usuarioId : null,
            encargado_id: $esEncargado ? $usuarioId : null,
            qr_entrada_id: $qrValido->id

        );

        $exito = $esEstudiante 
            ? $this->asistenciaRepository->crearAsistenciaParaEstudiante($asistencia, $sesionAbierta->id, $usuarioId, $qrValido->id)
            : $this->asistenciaRepository->crearAsistenciaParaEncargado($asistencia, $sesionAbierta->id, $usuarioId, $qrValido->id);

        if (!$exito) {
            throw new InternalServerException("Error al procesar el registro de asistencia en la base de datos.");
        }

        $asistencia->id = $exito;
        return $asistencia;
    }
    public function finalizarAsistencia(?string $observaciones,string $token) {
        // 1. Validar Sesión
        $sesionAbierta = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        if (!$sesionAbierta) {
            throw new BadRequestException("No hay una sesión abierta para finalizar la asistencia.");
        }

        // 2. Obtener Usuario del Microservicio
        $url = Secrets::microservicioUsuariosURL() . "/api/auth/me";
        $dataUsuario = RequestUtils::fetch($url, 'GET');
        
        $usuarioId = $dataUsuario['id'];
        $rol = strtolower($dataUsuario['rol'] ?? 'desconocido');
        $fechaSalida = date('Y-m-d H:i:s');

        // 3. Determinar campos según el Rol
        $esEstudiante = ($rol === RolEnum::ESTUDIANTE->value);
        $esEncargado = ($rol === RolEnum::ENCARGADO->value);
        if (!$esEstudiante && !$esEncargado) {
            throw new BadRequestException("El rol '$rol' no está autorizado para finalizar asistencias.");
        }
        
        // 4. Verificación de existencia (Lógica Unificada)
        $asistenciaExistente = $esEstudiante 
            ? $this->asistenciaRepository->obtenerAsistenciaParaEstudianteEnSesionDatoEstado($sesionAbierta->id, $usuarioId, AsistenciaEstadoEnum::PRESENTE->value)
            : $this->asistenciaRepository->obtenerAsistenciaParaEncargadoEnSesionDatoEstado($sesionAbierta->id, $usuarioId, AsistenciaEstadoEnum::PRESENTE->value);
        
        if (!$asistenciaExistente) {
            throw new NotFoundException("No tienes una asistencia registrada en la sesión actual para finalizar.");
        }

        $qrValido = $this->asistenciaRepository->obtenerQRDadoEstadoYToken(EstadoGeneralEnum::ACTIVO->value, $token);
        if (!$qrValido || $qrValido->estado !== EstadoGeneralEnum::ACTIVO->value) {
            throw new BadRequestException("El token QR proporcionado no es válido o no está activo.");
        }
        $asistenciaExistente->qr_salida_id = $qrValido->id;

        // 5. Actualizar Objeto Asistencia
        $asistenciaExistente->fecha_salida = $fechaSalida;
        $asistenciaExistente->estado = AsistenciaEstadoEnum::FINALIZADO->value;
        $asistenciaExistente->observaciones = $observaciones;
        // 6. Persistencia Dinámica
        $exito = $esEstudiante 
            ? $this->asistenciaRepository->cerrarAsistenciaParaEstudiante($asistenciaExistente, $sesionAbierta->id, $usuarioId, $qrValido->id)
            : $this->asistenciaRepository->cerrarAsistenciaParaEncargado($asistenciaExistente, $sesionAbierta->id, $usuarioId, $qrValido->id);
        if (!$exito) {
            throw new InternalServerException("Error al procesar la finalización de asistencia en la base de datos.");
        }
        return $asistenciaExistente;
    }
    public function actualizarAsistencia(int $id,?string $observaciones){

        $exito = $this->asistenciaRepository->actualizarAsistenciaObservaciones($id, $observaciones);
        if (!$exito) {
            throw new InternalServerException("Error al actualizar la asistencia en la base de datos.");
        }
         
        return $this->obtenerAsistenciaPorId($id);
    }
}