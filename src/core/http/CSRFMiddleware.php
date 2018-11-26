<?php

namespace core\http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CSRFMiddleware extends Middleware
{

    public $tokenName = 'scrf-token';

    protected function generateToken()
    {
        return md5(microtime());
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strtolower($request->getMethod()) === 'post' or
            strtolower($request->getMethod()) === 'put' or
            strtolower($request->getMethod()) === 'patch' or
            strtolower($request->getMethod()) === 'delete') {
            $parsedBody = $request->getParsedBody();
            $queryParams = $request->getQueryParams();
            $header = $request->getHeader('X-CSRF-Token');
            $token = null;
            if (isset($parsedBody[$this->tokenName])) {
                $token = $parsedBody[$this->tokenName];
            } elseif (isset($queryParams[$this->tokenName])) {
                $token = $queryParams[$this->tokenName];
            } elseif (!empty($header)) {
                $token = array_shift($header);
            }
            $validToken = (isset($_SESSION[$this->tokenName]) and $token === $_SESSION[$this->tokenName]);
            $request = $request->withAttribute('validToken', $validToken);
        }

        $_SESSION[$this->tokenName] = $this->generateToken();

        return $this->next->process($request, $handler);
    }
}