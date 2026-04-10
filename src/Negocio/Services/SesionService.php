<?php
namespace App\Negocio\Services;

use App\Datos\Config\Secrets;
use App\Datos\Models\Sesion;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Dtos\Sesion\SesionUpdateDTO;
use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Exceptions\NotFoundException;
use App\Shared\Enums\SesionEstadoEnum;
use App\Shared\Utils\RequestUtils;
use Throwable;

class SesionService{
    public function __construct(
        private SesionRepository $sesionRepository
    ){}
    public function listarSesiones(): array {
        
        $sesiones = $this->sesionRepository->listarSesiones();
        if (empty($sesiones)) {
            return [];
        }

        $idsEncargados = [];
        foreach ($sesiones as $s) {
            if ($s->encargado_apertura_id) $idsEncargados[] = $s->encargado_apertura_id;
            if ($s->encargado_cierre_id) $idsEncargados[] = $s->encargado_cierre_id;
        }
        $idsUnicos = array_values(array_unique($idsEncargados));

        $url = Secrets::microservicioUsuariosURL() . "/api/encargado/obtener-encargados";
        
        $dataUsuarios = RequestUtils::fetch($url, 'POST', ['ids' => $idsUnicos]);

        $mapaUsuarios = [];
        foreach ($dataUsuarios as $usuario) {
            $mapaUsuarios[$usuario['id']] = $usuario;
        }

        foreach ($sesiones as $s) {
            if (isset($mapaUsuarios[$s->encargado_apertura_id])) {
                $s->encargado_apertura = $mapaUsuarios[$s->encargado_apertura_id];
            }

            if ($s->encargado_cierre_id && isset($mapaUsuarios[$s->encargado_cierre_id])) {
                $s->encargado_cierre = $mapaUsuarios[$s->encargado_cierre_id];
            }
        }

        return $sesiones;
    }
    
    public function buscarSesionPorId(int $id): Sesion {
        
        $sesion = $this->sesionRepository->buscarPorId($id);
        if (!$sesion) {
            throw new NotFoundException("La sesión con ID $id no existe.");
        }

        $ids = array_filter([$sesion->encargado_apertura_id, $sesion->encargado_cierre_id]);

        if (!empty($ids)) {
            
            $url = Secrets::microservicioUsuariosURL() . "/api/encargado/obtener-encargados";
            $usuarios = RequestUtils::fetch($url, 'POST', ['ids' => array_values(array_unique($ids))]);
            // 4. Crear mapa e hidratar el modelo
            $mapa = [];
            foreach ($usuarios as $u) {
                $mapa[$u['id']] = $u;
            }
            $sesion->encargado_apertura = $mapa[$sesion->encargado_apertura_id] ?? null;
            $sesion->encargado_cierre = $mapa[$sesion->encargado_cierre_id] ?? null;

           
        }

        return $sesion;
    }

    public function aperturarSesion(?string $observaciones): Sesion {
        $existeAperturaAbierta = $this->sesionRepository->obtenerUltimaSesionDadoEstado(SesionEstadoEnum::ABIERTA->value);
        if ($existeAperturaAbierta) {
            throw new BadRequestException("Ya existe una sesión abierta. No se puede aperturar otra hasta que se cierre la actual.");
        }
        $url = Secrets::microservicioUsuariosURL() . "/api/encargado/sesion";
        $res = RequestUtils::fetch($url);
        #$usuarioDto = Usuario::mapearUsuario($data['data']);
        $encargadoId = $res['encargado']['id'] ?? null;
        $fechaApertura = date('Y-m-d H:i:s');
        $nuevaSesion = new Sesion(
            id: null,
            fecha_apertura: $fechaApertura, 
            fecha_cierre: null,
            estado: null,
            observaciones: $observaciones,
            #encargado_apertura_id: $usuarioDto->encargado->id, 
            encargado_apertura_id: $encargadoId,
            encargado_cierre_id: null
        );

        
        $sesionCreadaId = $this->sesionRepository->aperturarSesion($nuevaSesion);
        if (!$sesionCreadaId) {
            throw new BadRequestException("Error al aperturar la sesión.");
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
            throw new BadRequestException("La sesión con ID $id no existe.");
        }

        if ($sesionExistente->estado === SesionEstadoEnum::CERRADA->value) {
            throw new BadRequestException("La sesión con ID $id ya está cerrada.");
        }

        $url = Secrets::microservicioUsuariosURL() . "/api/encargado/sesion";
    
        $usuario = RequestUtils::fetch($url);

        $encargadoId = $usuario['encargado']['id'] ?? null;

        if (!$encargadoId) {
            throw new BadRequestException("No se pudo identificar al encargado para realizar el cierre.");
        }
        $fechaCierre = date('Y-m-d H:i:s');
        $exitoCierre = $this->sesionRepository->cerrarSesion(
            id: $id,
            fecha_cierre: $fechaCierre,
            observaciones: $observaciones,
            encargado_cierre_id: $encargadoId
        );

        if (!$exitoCierre) {
            throw new BadRequestException("Error interno al intentar cerrar la sesión con ID $id.");
        }
        #return $sesionExistente;
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
        $existeAsistencias = $this->sesionRepository->existeAsistenciasEnSesion($id);
        if ($existeAsistencias) {
            throw new BadRequestException("No se puede eliminar la sesión con ID $id porque tiene asistencias asociadas.");
        }
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
}