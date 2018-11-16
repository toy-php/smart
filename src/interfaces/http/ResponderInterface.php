<?php

namespace interfaces\http;

use interfaces\domain\ModelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

interface ResponderInterface
{

    /**
     * Переадресовать запрос
     * @param UriInterface $uri
     * @return void
     */
    public function redirect(UriInterface $uri);

    /**
     * Сформировать и получить объект ответа
     * @param ModelInterface $model
     * @return ResponseInterface
     */
    public function response(ModelInterface $model): ResponseInterface;

}