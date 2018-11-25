<?php

namespace interfaces\http;

use interfaces\domain\ModelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

interface ResponderInterface
{

    /**
     * @param UriInterface $uri
     * @param array $params
     * @return ResponseInterface
     */
    public function redirect(UriInterface $uri, array $params = []): ResponseInterface;

    /**
     * Сформировать и получить объект ответа
     * @param ModelInterface $model
     * @return ResponseInterface
     */
    public function response(ModelInterface $model): ResponseInterface;

}