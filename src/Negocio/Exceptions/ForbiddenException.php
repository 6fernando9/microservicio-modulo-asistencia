<?php
namespace App\Negocio\Exceptions;
class ForbiddenException extends AppException {
    protected int $httpCode = 403;
}