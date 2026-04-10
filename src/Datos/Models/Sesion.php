<?php
namespace App\Datos\Models;
class Sesion {
    public function __construct(
        public ?int $id,
        public ?string $fecha_apertura,
        public ?string $fecha_cierre,
        public ?string $estado,
        public ?string $observaciones,
        public ?int $encargado_apertura_id,
        public ?int $encargado_cierre_id,
        public array $asistencias = [],
        public ?array $encargado_apertura = null,
        public ?array $encargado_cierre = null,
    ){}
}