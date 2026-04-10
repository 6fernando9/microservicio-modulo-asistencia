<?php
namespace App\Negocio\Dtos\Sesion;

use App\Shared\Utils\Resultado;
use DateTime;
use DateTimeZone;
use Exception;

class AsistenciaUpdateDTO {
    public function __construct(
        public ?string $observaciones,
        public string $fecha_llegada,
        public ?string $fecha_salida
    ){}

    public static function fromArray(array $data): Resultado {
        $observaciones = $data['observaciones'] ?? null;
        
        // Intentamos parsear y normalizar las fechas
        $fecha_llegada = self::parsearFecha($data['fecha_llegada'] ?? null);
        $fecha_salida = self::parsearFecha($data['fecha_salida'] ?? null);

        if (!$fecha_llegada) {
            return Resultado::error("La fecha de apertura es obligatoria o tiene un formato inválido.");
        }

        // Si se envió fecha de cierre pero el formato fue incorrecto
        if (isset($data['fecha_salida']) && $data['fecha_salida'] !== null && !$fecha_salida) {
            return Resultado::error("El formato de fecha_salida no es válido.");
        }
        #podria validarse las fechas para asegurar que fecha_cierre no sea anterior a fecha_apertura, pero eso lo dejo para el servicio o repositorio, ya que es una regla de negocio y no de formato.
        return Resultado::ok(new self(
            observaciones: $observaciones,
            fecha_llegada: $fecha_llegada,
            fecha_salida: $fecha_salida
        ));
    }

    /**
     * Esta es la clave: acepta ISO 8601 (Frontend) y Y-m-d H:i:s (DB)
     * y devuelve siempre el formato de DB para tu repositorio.
     */
    private static function parsearFecha(?string $fecha): ?string {
        if (!$fecha) return null;

        try {
            // El constructor de DateTime es "multiformato" por defecto
            $d = new DateTime($fecha);
            
            // Opcional: Asegurar que se guarde en la hora de Bolivia si viene con Z (UTC)
            $d->setTimezone(new DateTimeZone('America/La_Paz'));

            return $d->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
}