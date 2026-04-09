<?php
namespace App\Negocio\Exceptions;

use Exception;

abstract class AppException extends Exception {
    protected int $httpCode;

    public function __construct(string $message = "") {
        parent::__construct($message);
    }

    public function getHttpCode(): int {
        return $this->httpCode;
    }
}