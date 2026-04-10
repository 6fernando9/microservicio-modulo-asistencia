<?php
namespace App\Shared\Utils;
use App\Negocio\Exceptions\AppException;
use App\Negocio\Exceptions\BadRequestException;
use App\Negocio\Exceptions\InternalServerException;
use App\Negocio\Exceptions\NotFoundException;
use App\Negocio\Exceptions\UnauthorizedException;
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
    public static function isOkHttpCode(int $httpStatus){
        return $httpStatus == 200 || $httpStatus == 201 || $httpStatus == 204;
    }

    public static function fetch(string $url, string $method = 'GET', $body = null): array {
        $ch = curl_init($url);
        $token = self::getAuthToken();
        $method = strtoupper($method); 
        
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        
        if ($token) {
            $headers[] = "Authorization: " . $token;
        }

        // --- CONFIGURACIÓN DE TIMEOUT ---
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 segundos para conectar
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);        // 10 segundos máximo para la respuesta
        // --------------------------------

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 1. Error de conexión o Timeout
        if ($response === false) {
            throw new InternalServerException("Error de comunicación con el microservicio: $error");
        }

        $data = json_decode($response, true);

        // 2. Manejo dinámico de errores según el código HTTP
        if (!self::isOkHttpCode($httpCode)) {
            $mensaje = $data['message'] ?? "Error en el microservicio externo ($url)";

            match ($httpCode) {
                404 => throw new NotFoundException($mensaje),
                401, 403 => throw new UnauthorizedException($mensaje), // Si tienes esta excepción
                500 => throw new InternalServerException("Error crítico en el microservicio: $mensaje"),
                default => throw new BadRequestException($mensaje, $httpCode),
            };
        }

        return $data;
    }
}