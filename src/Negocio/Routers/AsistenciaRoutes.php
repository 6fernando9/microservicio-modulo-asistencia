<?php
namespace App\Negocio\Routers;

use App\Datos\Repository\AsistenciaRepository;
use App\Datos\Repository\SesionRepository;
use App\Negocio\Controllers\AsistenciaController;
use App\Negocio\Services\AsistenciaService;

class AsistenciaRoutes {
    public static function define($router, $db) {
        // 1. Instanciar Repositorios
        $asistenciaRepo = new AsistenciaRepository($db);
        $sesionRepo = new SesionRepository($db);

        // 2. Instanciar Servicio (pasando ambos repositorios)
        $service = new AsistenciaService($asistenciaRepo, $sesionRepo);
        
        // 3. Instanciar Controlador
        $controller = new AsistenciaController($service);
        
        // 4. Definición de Rutas
        // Obtener detalle de una asistencia (incluye hidratación de actor)
        $router->get('/api/asistencia/{id}/show', [$controller, 'obtenerAsistenciaPorId']);
        
        // Registrar llegada (Crear)
        $router->post('/api/asistencia/marcar-llegada', [$controller, 'crearAsistencia']);
        
        // Registrar salida (Finalizar)
        $router->put('/api/asistencia/marcar-salida', [$controller, 'finalizarAsistencia']);
        
        // Actualizar observaciones
        $router->put('/api/asistencia/{id}/update', [$controller, 'actualizarAsistencia']);
    }
}