<?php
namespace App\Negocio\Routers;

use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\QrRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Controllers\SesionController;
use App\Negocio\Services\QrService;
use App\Negocio\Services\SesionService;

class SesionRoutes {
    public static function define($router, $db) {
        
        $sesionRepo = new SesionRepository($db);
        $qrRepo = new QrRepository($db);
        $asistenciaRepo = new AsistenciaRepository($db);
        $qrService = new QrService($qrRepo, $sesionRepo, $asistenciaRepo);
        $service = new SesionService($sesionRepo, $qrRepo, $asistenciaRepo, $qrService);
        
        
        $controller = new SesionController($service);
        
        // 4. Definición de Rutas según tu estándar
        $router->get('/api/sesion/index', [$controller, 'listarSesiones']);
        $router->get('/api/sesion/{id}/show', [$controller, 'buscarSesionPorId']);
        $router->post('/api/sesion/aperturar', [$controller, 'aperturarSesion']);
        $router->put('/api/sesion/{id}/cerrar', [$controller, 'cerrarSesion']);
        $router->delete('/api/sesion/{id}/eliminar', [$controller, 'eliminarSesion']);
        $router->put('/api/sesion/{id}/update', [$controller, 'actualizarSesion']);
        $router->get('/api/sesion/{id}/asistencias', [$controller, 'obtenerAsistenciasDeSesion']);
        $router->get('/api/sesion/{id}/qrs', [$controller, 'obtenerQrsDeSesion']);
        $router->get('/api/sesion/{id}/solicitudes', [$controller, 'obtenerSolicitudesDeSesion']);
        $router->get('/api/sesion/activa', [$controller, 'obtenerSesionActiva']);
        $router->get('/api/sesion/activa/simple', [$controller, 'obtenerSesionActivaSimple']);
    }
}