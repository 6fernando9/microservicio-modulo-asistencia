<?php

use App\Datos\Config\Database;
use App\Negocio\Routers\AsistenciaRoutes;
use App\Negocio\Routers\QrRoutes;
use App\Negocio\Routers\SesionRoutes;
use App\Shared\Utils\Router;
//CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición de pre-vuelo (OPTIONS), responder 200 y salir
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
date_default_timezone_set('America/La_Paz');
require_once __DIR__ . '/../src/autoload.php';


try {
    // 2. Conexión a la DB
    $db = (new Database())->getConnectionPostgresDatabase();

    // 3. Inicializar Router
    $router = new Router();

    // 4. Cargar rutas
    SesionRoutes::define($router, $db);
    AsistenciaRoutes::define($router, $db);
    QrRoutes::define($router, $db);

    // 5. ¡Ejecutar!
    //levantar con php -S localhost:8080 -t public
    $router->dispatch();

} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'critical_error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}