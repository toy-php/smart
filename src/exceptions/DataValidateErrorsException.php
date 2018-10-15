<?php

namespace exceptions;

use Throwable;

class DataValidateErrorsException extends MultiException implements \JsonSerializable
{

    public function __construct(string $message = "Validate errors", int $code = 412, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param mixed $offset
     * @param DataValidateErrorException $value
     * @throws InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof DataValidateErrorException){
            throw new InvalidArgumentException('Передан неверный тип исключения');
        }
        $this->innerOffsetSet($offset, $value);
    }

    /**
     * Наличие ошибок
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'errors' => array_map(function (DataValidateErrorException $exception){
                return [
                    'status' => $exception->getCode(),
                    'source' => [$exception->getKey() => $exception->getValue()],
                    'detail' => $exception->getMessage()
                ];
            }, $this->data)
        ];
    }
}