<?php
namespace App\Shared\Utils;

class Router {
    private array $routes = [];

    public function get(string $path, array $handler) {
        $path = '/' . ltrim($path, '/');
        $this->routes['GET:' . $path] = $handler;
    }

    public function post(string $path, array $handler) {
        $path = '/' . ltrim($path, '/');
        $this->routes['POST:' . $path] = $handler;
    }
    public function put(string $path, array $handler) {
        $path = '/' . ltrim($path, '/');
        $this->routes['PUT:' . $path] = $handler;
    }

    public function delete(string $path, array $handler) {
        $path = '/' . ltrim($path, '/');
        $this->routes['DELETE:' . $path] = $handler;
    }
  

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['PATH_INFO'] ?? '/';
        $path = '/' . ltrim($path, '/');

        if (empty($path)) $path = '/';

        $key = $method . ':' . $path;

        // if (isset($this->routes[$key])) {
        //     $handler = $this->routes[$key];
        //     return call_user_func($handler);
        // }
        foreach ($this->routes as $routeKey => $handler) {
            // Separamos el método del path de la ruta definida
            list($routeMethod, $routePath) = explode(':', $routeKey, 2);

            if ($method !== $routeMethod) continue;

            // Convertimos /api/tipo-material/{id} en una regex
            $pattern = preg_replace('/\{[a-zA-Z0-9]+\}/', '([a-zA-Z0-9]+)', $routePath);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Quitamos la ruta completa del array de coincidencias
                
                // Si hay coincidencias (como el ID), se pasan como argumentos al controlador
                return call_user_func_array($handler, $matches);
            }
        }


        http_response_code(404);
        echo json_encode([
            'error' => 'Ruta no encontrada',
            'buscado' => $key
        ]);
    }
}