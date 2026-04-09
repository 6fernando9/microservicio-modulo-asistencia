<?php

namespace App\Shared\Enums;

use InvalidArgumentException;

enum SesionEstadoEnum:string
{
    case ABIERTA = 'abierta';
    case CERRADA = 'cerrada';
    

    public function label(): string
    {
        return match ($this) {
            self::ABIERTA => 'abierta',
            self::CERRADA => 'cerrada',
            
        };
    }
    public static function fromLabel(string $label): self
    {
        return match(strtolower(trim($label))) {
            'abierta' => self::ABIERTA,
            'cerrada' => self::CERRADA,
            default => throw new InvalidArgumentException("El estado '$label' no es un estado válido."),
        };
    }
}
