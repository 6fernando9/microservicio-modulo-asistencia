<?php
namespace App\Negocio\Exceptions;
class InternalServerException extends AppException {
    protected int $httpCode = 500;
}