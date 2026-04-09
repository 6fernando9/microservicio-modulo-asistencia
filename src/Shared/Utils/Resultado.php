<?php
namespace App\Shared\Utils;

class Resultado {
    private function __construct(
        private readonly bool $exito,
        private readonly mixed $valor = null,
        private readonly ?string $error = null
    ) {}

    public static function ok(mixed $valor): self {
        return new self(true, valor: $valor);
    }

    public static function error(string $mensaje): self {
        return new self(false, error: $mensaje);
    }

    public function esExitoso(): bool { return $this->exito; }
    public function getValor(): mixed { return $this->valor; }
    public function getError(): ?string { return $this->error; }
}