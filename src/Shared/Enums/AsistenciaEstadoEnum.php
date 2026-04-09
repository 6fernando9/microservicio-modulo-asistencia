<?php

namespace App\Shared\Enums;

use InvalidArgumentException;

enum AsistenciaEstadoEnum:string
{
    case PRESENTE = 'presente';
    case FINALIZADO = 'finalizado';
    case CANCELADO = 'cancelado';

    

    public function label(): string
    {
        return match ($this) {
            self::PRESENTE => 'presente',
            self::FINALIZADO => 'finalizado',
            self::CANCELADO => 'cancelado',
            
        };
    }
    public static function fromLabel(string $label): self
    {
        return match(strtolower(trim($label))) {
            'presente' => self::PRESENTE,
            'finalizado' => self::FINALIZADO,
            'cancelado' => self::CANCELADO,
            default => throw new InvalidArgumentException("El estado '$label' no es un estado válido."),
        };
    }
}
