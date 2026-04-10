<?php
namespace App\Datos\Config;

class Secrets {
    private static bool $loaded = false;

    private static function init() {
        if (!self::$loaded) {
            // Ajusta la ruta según tu estructura de carpetas
            EnvLoader::load(dirname(__DIR__, 3) . '/.env');
            self::$loaded = true;
        }
    }

    public static function get(string $key, $default = null) {
        self::init();
        return $_ENV[$key] ?? $default;
    }

    // Métodos específicos para BD para mayor claridad
    public static function dbHost() { return self::get('DB_HOST', 'localhost'); }
    public static function dbPort() { return self::get('DB_PORT', '5432'); }
    public static function dbName() { return self::get('DB_NAME'); }
    public static function dbUser() { return self::get('DB_USER'); }
    public static function dbPass() { return self::get('DB_PASS'); }
    public static function jwtSecretKey() { return self::get('JWT_SECRET_KEY', 'supersecretkey'); }
    public static function jwtExpirationMinutes() { return self::get('JWT_EXPIRATION_MINUTES', 60); }
    public static function jwtAlgorithm() { return self::get('JWT_ALGORITHM', 'HS256'); }
    
    public static function microservicioUsuariosURL() { return self::get('MS_USUARIOS_URL'); }
    public static function microservicioArticulosURL() { return self::get('MS_ARTICULOS_URL'); }
    public static function microservicioSolicitudesURL() { return self::get('MS_SOLICITUDES_URL'); }
    public static function microservicioAsistenciasURL() { return self::get('MS_ASISTENCIAS_URL'); }
}