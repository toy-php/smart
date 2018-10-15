<?php

namespace exceptions;

use Throwable;

class NotFoundHttpException extends HttpException
{

    public function __construct(string $message = "", int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}