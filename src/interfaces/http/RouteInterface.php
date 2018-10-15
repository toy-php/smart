<?php

namespace interfaces\http;

use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{

    /**
     * Соответствует ли маршрут запросу
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function isMatch(ServerRequestInterface $request): bool;

    /**
     * Получить совпадения запроса с маршрутом
     * @return array
     */
    public function getArguments(): array;

}