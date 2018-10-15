<?php

namespace core\http;

use Psr\Http\Server\MiddlewareInterface;

abstract class Middleware implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface | null
     */
    protected $next;

    public function setNext(MiddlewareInterface $middleware = null)
    {
        $this->next = $middleware;
    }

}