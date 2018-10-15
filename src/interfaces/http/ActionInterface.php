<?php

namespace interfaces\http;

use Psr\Http\Server\RequestHandlerInterface;

interface ActionInterface extends RequestHandlerInterface
{

    /**
     * Установить найденые совпадения запроса с маршрутом
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments);
}