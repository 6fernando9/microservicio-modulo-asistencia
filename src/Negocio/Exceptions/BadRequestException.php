<?php
namespace App\Negocio\Exceptions;
class BadRequestException extends AppException {
    protected int $httpCode = 400;
}