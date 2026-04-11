<?php
namespace App\Negocio\Routers;

use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\QrRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Services\QrService;
use App\Negocio\Controllers\QrController;

class QrRoutes{
    public static function define($router, $db) {
        $qrRepo = new QrRepository($db);
        $sesionRepo = new SesionRepository($db);
        $asistenciaRepo = new AsistenciaRepository($db);
        $service = new QrService($qrRepo, $sesionRepo, $asistenciaRepo);
        $controller = new QrController($service);
        
        $router->post('/api/qr/crear', [$controller, 'crearQR']);
        $router->get('/api/qr/{id}/show', [$controller, 'obtenerQR']);
        $router->put('/api/qr/{id}/inhabilitar', [$controller, 'inhabilitarQR']);
        $router->delete('/api/qr/{id}/eliminar', [$controller, 'eliminarQR']);
    }
}