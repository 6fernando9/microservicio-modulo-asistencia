<?php
namespace App\Datos\Models;
class Asistencia {
    public function __construct(
        public ?int $id,
        public string $fecha_llegada,
        public ?string $fecha_salida,
        public ?string $estado,
        public ?string $observaciones,
        public ?int $encargado_id,
        public ?int $estudiante_id,
        public int $sesion_id,
        public ?Sesion $sesion = null,
        public bool $es_cerrado_por_sistema = false,
        public ?array $estudiante = null,
        public ?array $encargado = null,
        public ?int $qr_entrada_id = null,
        public ?int $qr_salida_id = null
    ){}
}