<?php
namespace App\Shared\Utils;
use App\Negocio\Exceptions\AppException;

use Throwable;
class RequestUtils {    
    public static function sendResponse(mixed $data, int $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    /**
     * El "Muro" que atrapa errores de Negocio (40x) e Infraestructura (500)
     */
    public static function handleError(Throwable $e) {
        // Si es una de nuestras excepciones personalizadas (AppException)
        if ($e instanceof AppException) {
            return RequestUtils::sendResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'date' => date('Y-m-d H:i:s'),
                #'error' => get_class($e)
            ], $e->getHttpCode());
        }

        // Si es un error crítico (Base de datos, Error de sintaxis, etc)
        error_log("CRITICAL ERROR: " . $e->getMessage()); 
        
        return RequestUtils::sendResponse([
            'status' => 'critical_error',
            'message' => 'Hubo un problema interno en el servidor.',
            'date' => date('Y-m-d H:i:s'),
        ], 500);
    }


    // public static function getAuthToken(): ?string {
    //     $header = null;

    //     // 1. Intentar obtener de las cabeceras estándar
    //     if (isset($_SERVER['Authorization'])) {
    //         $header = $_SERVER['Authorization'];
    //     } 
    //     // 2. Intentar obtener de REDIRECT_HTTP_AUTHORIZATION (algunos servidores Apache)
    //     elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    //         $header = $_SERVER['HTTP_AUTHORIZATION'];
    //     } 
    //     elseif (function_exists('apache_request_headers')) {
    //         $headers = apache_request_headers();
    //         if (isset($headers['Authorization'])) {
    //             $header = $headers['Authorization'];
    //         }
    //     }

    //     if (!$header) {
    //         return null;
    //     }

    //     // 3. Limpiar el prefijo "Bearer "
    //     // Usamos una expresión regular para que no importe si "Bearer" está en mayúsculas o minúsculas
    //     if (preg_match('/Bearer\s(\S+)/i', $header, $matches)) {
    //         return $matches[1];
    //     }

    //     return null;
    // }
    public static function getAuthToken(): ?string {
        $header = null;

        // Buscamos en las diferentes posibles ubicaciones de la cabecera Authorization
        if (isset($_SERVER['Authorization'])) {
            $header = $_SERVER['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
            }
        }

        return $header; // Retorna el string completo "Bearer eyJhbG..." o null
    }
    
    public static function getJsonBody(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? $_POST ?? [];
    }
    public static function redirect(string $url) {
        header("Location: $url");
        exit;
    }
}