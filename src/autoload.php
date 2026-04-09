<?php
// src/autoload.php

spl_autoload_register(function ($clase) {
    // 1. Definimos el prefijo del proyecto (Namespace raíz)
    $prefix = 'App\\';

    // 2. Directorio base donde están las clases (Carpeta src/)
    // Como este archivo está en src/, __DIR__ apunta a src/
    $base_dir = __DIR__ . '/'; 

    // ¿La clase que intenta cargar PHP empieza con "App\"?
    $len = strlen($prefix);
    if (strncmp($prefix, $clase, $len) !== 0) {
        return; // No es una clase de nuestro sistema, ignorar
    }

    // 3. Obtenemos el nombre relativo quitando el prefijo
    // Ejemplo: App\Infraestructure\Config\Database -> Infraestructure\Config\Database
    $relative_class = substr($clase, $len);

    // 4. Convertimos el namespace en ruta de archivo real
    // Reemplazamos las barras invertidas (\) por barras de directorio (/)
    // Ejemplo: Infraestructure/Config/Database.php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // 5. Si el archivo existe físicamente, lo incluimos una sola vez
    if (file_exists($file)) {
        
        require_once $file;
    } else {
        // Opcional: Esto te ayuda a debuguear si una clase no carga
        // error_log("Autoload falló al buscar: " . $file);
    }
});