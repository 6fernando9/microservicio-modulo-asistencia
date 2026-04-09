<?php

namespace App\Shared\Enums;

use InvalidArgumentException;

enum EstadoGeneralEnum:string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'activo',
            self::INACTIVO => 'inactivo',
            
        };
    }
    public static function fromLabel(string $label): self
    {
        return match(strtolower(trim($label))) {
            'activo' => self::ACTIVO,
            'inactivo' => self::INACTIVO,
            default => throw new InvalidArgumentException("El estado '$label' no es un estado válido."),
        };
    }
}
