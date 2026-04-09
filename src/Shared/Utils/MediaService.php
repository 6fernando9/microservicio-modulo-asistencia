<?php
namespace App\Presentation\Services\Storage;

use App\Negocio\Exceptions\InternalServerException;
use CURLFile;
use Exception;

class MediaService {
    
    private const FOLDER_ROOT = "proyecto_biblioteca";
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    private const MAX_WIDTH = 800;

    public static function uploadImage(array $file, string $folder): string {
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new Exception("Formato no permitido. Use: " . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        $timestamp = time();
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'];
        $apiKey    = $_ENV['CLOUDINARY_API_KEY'];
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'];

        $folderPath = self::FOLDER_ROOT . "/" . $folder;
        $transformation = "w_" . self::MAX_WIDTH . ",c_scale,q_auto,f_auto";

        
        $paramsToSign = [
            'folder' => $folderPath,
            'timestamp' => $timestamp,
            'transformation' => $transformation
        ];
        ksort($paramsToSign);
        
        $signString = "";
        foreach ($paramsToSign as $key => $value) {
            $signString .= "$key=$value&";
        }
        $signString = rtrim($signString, '&') . $apiSecret;
        $signature = sha1($signString);

        
        $payload = [
            'file'           => new CURLFile($file['tmp_name']),
            'api_key'        => $apiKey,
            'timestamp'      => $timestamp,
            'signature'      => $signature,
            'folder'         => $folderPath,
            'transformation' => $transformation
        ];

        
        $ch = curl_init("https://api.cloudinary.com/v1_1/$cloudName/image/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            error_log("Cloudinary Error: " . ($result['error']['message'] ?? 'Desconocido'));
            throw new InternalServerException("Error al subir la imagen al servidor.");
        }

        return $result['secure_url'];
    }


    public static function deleteImage(string $imageUrl): bool {
        if (empty($imageUrl)) return false;

        $publicId = self::extractPublicId($imageUrl);
        if (!$publicId) return false;

        $timestamp = time();
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'];
        $apiKey    = $_ENV['CLOUDINARY_API_KEY'];
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'];

    
        $paramsToSign = "public_id=$publicId&timestamp=$timestamp" . $apiSecret;
        $signature = sha1($paramsToSign);

        $payload = [
            'public_id' => $publicId,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature
        ];

        $ch = curl_init("https://api.cloudinary.com/v1_1/$cloudName/image/destroy");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return ($response['result'] ?? '') === 'ok';
    }

    private static function extractPublicId(string $url): string {
    
        $parts = explode('/', $url);
        $rootIdx = array_search(self::FOLDER_ROOT, $parts);
        
        if ($rootIdx === false) return "";

        $pathParts = array_slice($parts, $rootIdx);
        $fullPath = implode('/', $pathParts);
        
        return preg_replace('/\.[^.]+$/', '', $fullPath);
    }
}