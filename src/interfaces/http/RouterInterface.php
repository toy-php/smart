<?php

namespace interfaces\http;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{

    /**
     * Найти обработчик запроса
     * @param ServerRequestInterface $request
     * @return ActionInterface
     */
    public function find(ServerRequestInterface $request): ActionInterface;

}