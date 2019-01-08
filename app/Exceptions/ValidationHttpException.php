<?php

namespace App\Exceptions;

use Exception;

class ValidationHttpException extends ResourceException
{
    public function __construct($message, $errors = null, Exception $previous = null, $headers = [], $code = 0)
    {
        parent::__construct($message, $errors, $previous, $headers, $code);
    }
}