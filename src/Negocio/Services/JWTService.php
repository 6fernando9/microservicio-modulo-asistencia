<?php
namespace App\Negocio\Services;

use App\Datos\Config\Secrets;
use App\Negocio\Exceptions\UnauthorizedException;

class JWTService {
    
    

    public static function getPasswordHash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    
    public static function verifyPassword(string $plainPassword, string $hashedPassword): bool {
        return password_verify($plainPassword, $hashedPassword);
    }

    
    public static function createAccessToken(array $data): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => Secrets::jwtAlgorithm()]);
        
        $payload = $data;
        $payload['exp'] = time() + (Secrets::jwtExpirationMinutes() * 60);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, Secrets::jwtSecretKey(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decodeToken(string $token): ?array {
        $token = str_replace("Bearer ", "", $token);
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthorizedException("Token malformado");
        }

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, Secrets::jwtSecretKey(), true);
        if (self::base64UrlEncode($validSignature) !== $signature) {
            throw new UnauthorizedException("Token inválido o firma incorrecta");
        }

        $data = json_decode(self::base64UrlDecode($payload), true);

        if (($data['exp'] ?? 0) < time()) {
            throw new UnauthorizedException("El token ha expirado");
        }

        return $data;
    }

    
    private static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}