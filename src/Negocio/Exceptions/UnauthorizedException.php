<?php
namespace App\Negocio\Exceptions;
class UnauthorizedException extends AppException {
    protected int $httpCode = 401;
}