<?php
namespace App\Negocio\Exceptions;
class NotFoundException extends AppException {
    protected int $httpCode = 404;
}