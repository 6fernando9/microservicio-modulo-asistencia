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
        
        public int $cantidad_asistencia_presente = 0,
        public int $cantidad_asistencia_finalizado = 0,
        public int $cantidad_asistencia_cancelado = 0,

        public int $cantidad_qr_generados = 0,

        public int $cantidad_pendiente_devolucion = 0,
        public int $cantidad_anulada = 0,
        public int $cantidad_finalizado = 0,
        public ?array $encargado_apertura = null,
        public ?array $encargado_cierre = null,
    ){}
}