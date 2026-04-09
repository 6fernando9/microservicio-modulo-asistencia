<?php

namespace App\Negocio\Dtos\Sesion;

use App\Shared\Utils\Resultado;

class SesionAperturaDTO {
    public function __construct(
        //public ?string $fecha_apertura, //esta data viene del microservicio
        public ?string $observaciones = null,
        //public ?int $encargado_apertura_id //esta data viene del microservicio
    ){}
    public static function fromArray(array $data): Resultado {
        $observaciones = $data['observaciones'] ?? null;
       
        return Resultado::ok(
            new self(
                //fecha_apertura: $data['fecha_apertura'] ?? null,
                observaciones: $observaciones,
                //encargado_apertura_id: $data['encargado_apertura_id'] ?? null
            )
        );
    }
}