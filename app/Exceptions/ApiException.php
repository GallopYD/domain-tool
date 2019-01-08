<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use RuntimeException;

class ApiException extends RuntimeException
{
    protected $slug;

    public function __construct($errmsg, $status_code = 422)
    {
        throw new HttpException($status_code, $errmsg);
    }
}