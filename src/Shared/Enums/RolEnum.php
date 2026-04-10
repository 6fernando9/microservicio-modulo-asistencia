<?php

namespace App\Shared\Enums;

use InvalidArgumentException;

enum RolEnum:string
{
    case ESTUDIANTE = 'estudiante';
    case ENCARGADO = 'encargado';
    

    public function label(): string
    {
        return match ($this) {
            self::ESTUDIANTE => 'estudiante',
            self::ENCARGADO => 'encargado',
            
        };
    }
    public static function fromLabel(string $label): self
    {
        return match(strtolower(trim($label))) {
            'estudiante' => self::ESTUDIANTE,
            'encargado' => self::ENCARGADO,
            default => throw new InvalidArgumentException("El rol '$label' no es un rol válido."),
        };
    }
}
