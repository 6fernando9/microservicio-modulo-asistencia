<?php

use App\Datos\Config\Database;

require_once dirname(__DIR__,1) . '/src/autoload.php';


$database = new Database();
$db = $database->getConnectionPostgresDatabase();

try {
    // 1. Crear tabla de control de versiones si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS control_migraciones (
        id SERIAL PRIMARY KEY,
        nombre_archivo VARCHAR(255) UNIQUE NOT NULL,
        ejecutado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Escanear la carpeta de migraciones
    $directorio = dirname(__DIR__,1) . '/Migrations/*.sql';
    $archivos = glob($directorio);
    sort($archivos); // Ordenar por nombre (001, 002...)

    echo "--- Iniciando Migraciones ---\n";

    foreach ($archivos as $rutaCompleta) {
        $nombreArchivo = basename($rutaCompleta);

        // 3. Verificar si ya se ejecutó
        $check = $db->prepare("SELECT id FROM control_migraciones WHERE nombre_archivo = ?");
        $check->execute([$nombreArchivo]);

        if (!$check->fetch()) {
            echo "Ejecutando: $nombreArchivo... ";
            
            $sql = file_get_contents($rutaCompleta);
            
            // Usamos transacciones para seguridad total
            $db->beginTransaction();
            try {
                $db->exec($sql);
                
                // Registrar éxito
                $registrar = $db->prepare("INSERT INTO control_migraciones (nombre_archivo) VALUES (?)");
                $registrar->execute([$nombreArchivo]);
                
                $db->commit();
                echo "✅ Completado\n";
            } catch (Exception $e) {
                $db->rollBack();
                echo "❌ FALLÓ: " . $e->getMessage() . "\n";
                exit; // Detener todo si un archivo falla
            }
        } else {
            echo "Saltando: $nombreArchivo (Ya ejecutada)\n";
        }
    }

    echo "--- Proceso finalizado ---\n";

} catch (PDOException $e) {
    echo "Error crítico: " . $e->getMessage();
}