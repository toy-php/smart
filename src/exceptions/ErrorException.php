<?php

namespace exceptions;

class ErrorException extends Exception
{

    protected $key;
    protected $value;

    public function __construct(string $key, $value, string $message = '', int $code = 412, \Throwable $previous = null)
    {
        $this->key = $key;
        $this->value = $value;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }


}