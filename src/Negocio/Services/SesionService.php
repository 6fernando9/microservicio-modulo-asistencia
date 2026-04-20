<?php
namespace App\Negocio\Services;

use App\Datos\Config\Secrets;
use App\Datos\Models\Sesion;
use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\QrRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Dtos\Sesion\SesionUpdateDTO;
use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Exceptions\InternalServerException;
use App\Negocio\Exceptions\NotFoundException;
use App\Shared\Enums\RolEnum;
use App\Shared\Enums\SesionEstadoEnum;
use App\Shared\Utils\RequestUtils;
use Throwable;

class SesionService{
    public function __construct(
        private SesionRepository $sesionRepository,
        private QrRepository $qrRepository,
        private AsistenciaRepository $asistenciaRepository,
        private QrService $qrService
    ){}
    public function listarSesiones(): array {
        
        $sesiones = $this->sesionRepository->listarSesiones();
        $sesionActiva = $this->obtenerSesionActiva();
        if (empty($sesiones)) {
            return [
                'sesiones' => [],
                'sesion_activa' => null,
                'qr_encargado' => null,
                'qr_estudiante' => null
            ];
        }

        $idsEncargados = [];
        $idsSesiones = [];
        foreach ($sesiones as $s) {
            if ($s->encargado_apertura_id) $idsEncargados[] = $s->encargado_apertura_id;
            if ($s->encargado_cierre_id) $idsEncargados[] = $s->encargado_cierre_id;
        }

        if ($sesionActiva && $sesionActiva->encargado_apertura_id) {
            $idsSesiones[] = $sesionActiva->id;
            $idsEncargados[] = $sesionActiva->encargado_apertura_id;
        }
        $idsUnicos = array_values(array_unique($idsEncargados));
        $idsSesionesUnicos = array_values(array_unique($idsSesiones));

        $mapaUsuarios = [];
        if (!empty($idsUnicos)) {
            $url = Secrets::microservicioUsuariosURL() . "/api/encargado/obtener-encargados";
            $dataUsuarios = RequestUtils::fetch($url, 'GET', ['ids' => $idsUnicos]);
            
            foreach ($dataUsuarios as $usuario) {
                $mapaUsuarios[$usuario['id']] = $usuario;
            }
        }
        $mapaSolicitudes = [];
        if (!empty($idsSesionesUnicos)) {
            $urlSols = Secrets::microservicioSolicitudesURL() . "/api/solicitud/sesion/cantidades";
            
            $dataSols = RequestUtils::fetch($urlSols, 'GET', ['ids' => $idsSesionesUnicos]);
            foreach ($dataSols as $c) {
                $mapaSolicitudes[$c['sesion_id']] = $c;
            }
        }

        // foreach ($sesiones as $s) {
        //     if (isset($mapaUsuarios[$s->encargado_apertura_id])) {
        //         $s->encargado_apertura = $mapaUsuarios[$s->encargado_apertura_id];
        //     }

        //     if ($s->encargado_cierre_id && isset($mapaUsuarios[$s->encargado_cierre_id])) {
        //         $s->encargado_cierre = $mapaUsuarios[$s->encargado_cierre_id];
        //     }
        // }
        // foreach ($sesiones as $s) {
        //     $s->encargado_apertura = $mapaUsuarios[$s->encargado_apertura_id] ?? null;
        //     $s->encargado_cierre = $mapaUsuarios[$s->encargado_cierre_id] ?? null;
        // }
        // if ($sesionActiva) {
        //     $sesionActiva->encargado_apertura = $mapaUsuarios[$sesionActiva->encargado_apertura_id] ?? null;
        // }

        $inyectarDatos = function(Sesion $s) use ($mapaUsuarios, $mapaSolicitudes) {
            // Usuarios
            $s->encargado_apertura = $mapaUsuarios[$s->encargado_apertura_id] ?? null;
            $s->encargado_cierre = $mapaUsuarios[$s->encargado_cierre_id] ?? null;
            
            // Solicitudes
            if (isset($mapaSolicitudes[$s->id])) {
                $s->cantidad_pendiente_devolucion = $mapaSolicitudes[$s->id]['cantidad_pendiente_devolucion'] ?? 0;
                $s->cantidad_finalizado = $mapaSolicitudes[$s->id]['cantidad_finalizado'] ?? 0;
                $s->cantidad_anulada = $mapaSolicitudes[$s->id]['cantidad_anulada'] ?? 0;
            }
        };

        foreach ($sesiones as $s) $inyectarDatos($s);
        if ($sesionActiva) $inyectarDatos($sesionActiva);

        
        $qrsActivos = ['qr_encargado' => null, 'qr_estudiante' => null]; 

        if ($sesionActiva) {
            $qrsActivos = $this->obtenerQrsDeSesionActivos($sesionActiva->id);
        }

        return [
            'sesiones' => $sesiones,
            'sesion_activa' => $sesionActiva,
            'qr_encargado' => $qrsActivos['qr_encargado'],
            'qr_estudiante' => $qrsActivos['qr_estudiante']
        ];
    }
    
    public function buscarSesionPorId(int $id): Sesion {
        
        $sesion = $this->sesionRepository->buscarPorId($id);
        if (!$sesion) {
            throw new NotFoundException("La sesión con ID $id no existe.");
        }

        $ids = array_filter([$sesion->encargado_apertura_id, $sesion->encargado_cierre_id]);

        if (!empty($ids)) {
            
            $url = Secrets::microservicioUsuariosURL() . "/api/encargado/obtener-encargados";
            $usuarios = RequestUtils::fetch($url, 'GET', ['ids' => array_values(array_unique($ids))]);
            $mapa = [];
            foreach ($usuarios as $u) {
                $mapa[$u['id']] = $u;
            }
            $sesion->encargado_apertura = $mapa[$sesion->encargado_apertura_id] ?? null;
            $sesion->encargado_cierre = $mapa[$sesion->encargado_cierre_id] ?? null;
        }

        return $sesion;
    }
     public function buscarSesionPorIdSimple(int $id): Sesion {
        
        $sesion = $this->sesionRepository->buscarPorId($id);
        if (!$sesion) {
            throw new NotFoundException("La sesión con ID $id no existe.");
        }

        return $sesion;
    }

    public function aperturarSesion(?string $observaciones): Sesion {
        $existeAperturaAbierta = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        if ($existeAperturaAbierta) {
            throw new BadRequestException("Ya existe una sesión abierta. No se puede aperturar otra hasta que se cierre la actual.");
        }
        $url = Secrets::microservicioUsuariosURL() . "/api/usuario/encargado-sesion";
        $res = RequestUtils::fetch($url);

        $encargadoId = $res['encargado']['id'] ?? null;
        $fechaApertura = date('Y-m-d H:i:s');
        $nuevaSesion = new Sesion(
            id: null,
            fecha_apertura: $fechaApertura, 
            fecha_cierre: null,
            estado: null,
            observaciones: $observaciones,
            encargado_apertura_id: $encargadoId,
            encargado_cierre_id: null
        );

        
        $sesionCreadaId = $this->sesionRepository->aperturarSesion($nuevaSesion, $encargadoId);
        if (!$sesionCreadaId) {
            throw new BadRequestException("Error al aperturar la sesión.");
        }
        $qrEstudiante = $this->qrService->crearQR(RolEnum::ESTUDIANTE->value);
        if (!$qrEstudiante) {
            throw new BadRequestException("Error al crear el QR para estudiantes al aperturar la sesión.");
        }
        $qrEncargado = $this->qrService->crearQR(RolEnum::ENCARGADO->value);
        if (!$qrEncargado) {
            throw new BadRequestException("Error al crear el QR para encargados al aperturar la sesión.");
        }   
        $nuevaSesion->id = $sesionCreadaId;
        $nuevaSesion->estado = SesionEstadoEnum::ABIERTA->value;
        $nuevaSesion->encargado_apertura_id = $encargadoId;
        $nuevaSesion->fecha_apertura = $fechaApertura;
        $nuevaSesion->observaciones = $observaciones;
        return $nuevaSesion;

    }
    public function cerrarSesion(int $id, ?string $observaciones ){

        $sesionExistente = $this->sesionRepository->buscarPorId($id);
        if (!$sesionExistente) {
            throw new NotFoundException("La sesión con ID $id no existe.");
        }

        if ($sesionExistente->estado === SesionEstadoEnum::CERRADA->value) {
            throw new BadRequestException("La sesión con ID $id ya está cerrada.");
        }

        $url = Secrets::microservicioUsuariosURL() . "/api/usuario/encargado-sesion";
    
        $usuario = RequestUtils::fetch($url);

        $encargadoId = $usuario['encargado']['id'] ?? null;

        if (!$encargadoId) {
            throw new BadRequestException("No se pudo identificar al encargado para realizar el cierre.");
        }
        $fechaCierre = date('Y-m-d H:i:s');
        $sesionAActualizar = new Sesion(
            id: null,
            fecha_apertura: null,
            fecha_cierre: $fechaCierre,
            estado: null,
            observaciones: $observaciones,
            encargado_apertura_id: null,
            encargado_cierre_id: null
        );
        $exito = $this->sesionRepository->cerrarSesion($id, $sesionAActualizar, $encargadoId);

        if (!$exito) {
            throw new InternalServerException("Error interno al intentar cerrar la sesión con ID $id.");
        }
        $exito = $this->asistenciaRepository->marcarAsistenciasCerradasPorSistema($id);
        if (!$exito) {
            throw new InternalServerException("Error interno al intentar marcar asistencias como cerradas por sistema para la sesión con ID $id.");
        }
        $exito = $this->qrRepository->marcarQrsInactivosPorSistema($id);
        if (!$exito) {
            throw new InternalServerException("Error interno al intentar marcar QRs como inactivos por sistema para la sesión con ID $id.");
        }
        
        $sesionExistente->estado = SesionEstadoEnum::CERRADA->value;
        $sesionExistente->fecha_cierre = $fechaCierre;
        $sesionExistente->encargado_cierre_id = $encargadoId;
        $sesionExistente->observaciones = $observaciones;
        return $sesionExistente;
        
    }
    public function eliminarSesion(int $id): bool{
        $sesionExistente = $this->sesionRepository->buscarPorId($id);
        if (!$sesionExistente) {
            throw new BadRequestException("La sesión con ID $id no existe.");
        }
        $existeAsistencias = $this->asistenciaRepository->existeAsistenciasEnSesion($id);
        #$existeQr = $this->sesionRepository->existeQrEnSesion($id);
        if ($existeAsistencias ) {
            throw new BadRequestException("No se puede eliminar la sesión con ID $id porque tiene asistencias asociados.");
        }
        
        $data = RequestUtils::fetch(Secrets::microservicioSolicitudesURL() . "/api/solicitud/sesion/$id/existe", 'GET');
        
        #se asume que si existe una solicitud asociada a la sesion, entonces no se puede eliminar la sesion, aunque no tenga asistencias o qr asociados
        # en teoria lanza un 404

        #digamos que permite eliminar sesion aunque tenga qr asociado
        $eliminacion = $this->sesionRepository->eliminarSesion($id);
        if (!$eliminacion) {
            throw new BadRequestException("No se pudo eliminar la sesión con ID $id. Puede que no exista.");
        }
        return $eliminacion;
    }
    public function actualizarSesion(int $id, SesionUpdateDTO $dto){
        $sesionExistente = $this->sesionRepository->buscarPorId($id);
        if (!$sesionExistente) {
            throw new BadRequestException("La sesión con ID $id no existe.");
        }
        $estadoSesion = strtolower($sesionExistente->estado);
        if ($estadoSesion === SesionEstadoEnum::ABIERTA->value) {
            if ($dto->fecha_cierre !== null) {
                throw new BadRequestException("No se puede establecer una fecha de cierre para una sesión que está abierta.");
            }
        }   
        if ($estadoSesion === SesionEstadoEnum::CERRADA->value) {
            if ($dto->fecha_cierre === null) {
                throw new BadRequestException("La fecha de cierre es obligatoria para una sesión cerrada.");
            }
        }
        #validamos que la fecha de entrada no sea mayor a hoy
        if ($dto->fecha_apertura > date('Y-m-d H:i:s')) {
            throw new BadRequestException("La fecha de apertura no puede ser mayor a la fecha actual.");
        }
        if ($dto->fecha_cierre !== null && $dto->fecha_cierre > date('Y-m-d H:i:s')) {
            throw new BadRequestException("La fecha de cierre no puede ser mayor a la fecha actual.");
        }
        #validamos que la fecha de apertura no sea mayor a la fecha de cierre
        if ($dto->fecha_cierre !== null && $dto->fecha_apertura > $dto->fecha_cierre) {
            throw new BadRequestException("La fecha de apertura no puede ser mayor a la fecha de cierre.");
        }
        $sesionAActualizar = new Sesion(
            id: $id,
            fecha_apertura: $dto->fecha_apertura,
            fecha_cierre: $dto->fecha_cierre,
            observaciones: $dto->observaciones,
            estado: null,
            encargado_apertura_id: null,
            encargado_cierre_id: null
        );
        $actualizacion = $this->sesionRepository->actualizarSesion($id,$sesionAActualizar);
        if (!$actualizacion) {
            throw new BadRequestException("No se pudo actualizar la sesión con ID $id. Puede que no exista.");
        }
        #return $this->buscarSesionPorId($id);
        $sesionExistente->fecha_apertura = $dto->fecha_apertura;
        $sesionExistente->fecha_cierre = $dto->fecha_cierre;
        $sesionExistente->observaciones = $dto->observaciones;
        return $sesionExistente;

    }
    public function obtenerAsistenciasDeSesion(int $sesionId): array {
        $sesionExistente = $this->sesionRepository->buscarPorId($sesionId);
        if (!$sesionExistente) {
            throw new NotFoundException("La sesión con ID $sesionId no existe.");
        }

        $asistencias = $this->asistenciaRepository->obtenerAsistenciasDeSesion($sesionId);
        if (empty($asistencias)) {
            return [];
        }

        $idsActores = [];
        foreach ($asistencias as $a) {
            if ($a->estudiante_id) $idsActores[] = $a->estudiante_id;
            if ($a->encargado_id) $idsActores[] = $a->encargado_id;
        }
        
        $idsUnicos = array_values(array_unique($idsActores));

        if (!empty($idsUnicos)) {
            
            $url = Secrets::microservicioUsuariosURL() . "/api/usuario/obtener-usuarios";
            $response = RequestUtils::fetch($url, 'GET', ['ids' => $idsUnicos]);
            $dataUsuarios = $response['data'] ?? $response;
            $mapaUsuarios = [];
            foreach ($dataUsuarios as $usuario) {
                
                $mapaUsuarios[(int)$usuario['id']] = $usuario;
            }
            // USAMOS REFERENCIA (&) para modificar los objetos originales dentro del array $asistencias
            foreach ($asistencias as &$asistencia) {
                $eId = $asistencia->estudiante_id ? (int)$asistencia->estudiante_id : null;
                $encId = $asistencia->encargado_id ? (int)$asistencia->encargado_id : null;
                if ($eId && isset($mapaUsuarios[$eId])) {
                    $asistencia->estudiante = $mapaUsuarios[$eId];
                }
                
                if ($encId && isset($mapaUsuarios[$encId])) {
                    $asistencia->encargado = $mapaUsuarios[$encId];
                }
            }
            unset($asistencia);
        }

        return $asistencias;
    }
    public function obtenerSolicitudesDeSesion(int $sesionId): array {
        $sesionExistente = $this->sesionRepository->buscarPorId($sesionId);
        if (!$sesionExistente) {
            throw new NotFoundException("La sesión con ID $sesionId no existe.");
        }
        $data = RequestUtils::fetch(Secrets::microservicioSolicitudesURL() . "/api/solicitud/sesion/$sesionId/index");
        return $data;
    }
    public function obtenerQrsDeSesion(int $sesionId): array {
        $sesionExistente = $this->sesionRepository->buscarPorId($sesionId);
        if (!$sesionExistente) {
            throw new NotFoundException("La sesión con ID $sesionId no existe.");
        }
        $qrs = $this->qrRepository->obtenerQrsDeSesion($sesionId);
        return $qrs;
    }
    // public function obtenerSesionActiva(): ?Sesion{
    //     $sesionActiva = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
      
    //     return $sesionActiva;
    // }
    public function obtenerSesionActivaSimple(): ?Sesion {
        return $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
    }
    public function obtenerSesionActiva(): ?Sesion {
        
        $sesionActiva = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        
        if (!$sesionActiva) {
            return null;
        }

        if ($sesionActiva->encargado_apertura_id) {
            
            $urlUser = Secrets::microservicioUsuariosURL() . "/api/encargado/obtener-encargados";
            $usuarios = RequestUtils::fetch($urlUser, 'GET', ['ids' => [$sesionActiva->encargado_apertura_id]]);
            if (!empty($usuarios)) {
                $sesionActiva->encargado_apertura = $usuarios[0];
            }
            
        }

        $urlSols = Secrets::microservicioSolicitudesURL() . "/api/solicitud/sesion/cantidades";
        $dataSols = RequestUtils::fetch($urlSols, 'GET', ['ids' => [$sesionActiva->id]]);
        if (!empty($dataSols)) {
            $conteo = $dataSols[0];
            $sesionActiva->cantidad_pendiente_devolucion = $conteo['cantidad_pendiente_devolucion'] ?? 0;
            $sesionActiva->cantidad_finalizado = $conteo['cantidad_finalizado'] ?? 0;
            $sesionActiva->cantidad_anulada = $conteo['cantidad_anulada'] ?? 0;
        }
        
        return $sesionActiva;
    }
    public function obtenerQrsDeSesionActivos(int $sesionId) {
        
        $objetivos = [RolEnum::ENCARGADO->value, RolEnum::ESTUDIANTE->value];
        
        $qrs = $this->qrRepository->obtenerQrsActivosDeSesion($sesionId, $objetivos);
        
        $data = [
            'qr_encargado' => null,
            'qr_estudiante' => null
        ];

        foreach ($qrs as $qr) {
            if (strtolower($qr->objetivo) === RolEnum::ENCARGADO->value) {
                $data['qr_encargado'] = $qr;
            } elseif (strtolower($qr->objetivo) === RolEnum::ESTUDIANTE->value) {
                $data['qr_estudiante'] = $qr;
            }
        }

        return $data;
    }

  
   
}