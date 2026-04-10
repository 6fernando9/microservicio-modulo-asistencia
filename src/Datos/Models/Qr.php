<?php
namespace App\Datos\Models;
class Qr {
    public function __construct(
        public ?int $id,
        public ?string $token,
        public ?string $estado,
        public string $objetivo,
        public ?int $sesion_id,
        public ?Sesion $sesion = null,
    ){}
    
}