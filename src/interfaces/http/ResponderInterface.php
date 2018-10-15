<?php

namespace interfaces\http;

use Psr\Http\Message\ResponseInterface;

interface ResponderInterface
{

    /**
     * Сформировать и получить объект ответа
     * @param array $data
     * @return ResponseInterface
     */
    public function response(array $data): ResponseInterface;
}