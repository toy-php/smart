<?php

namespace core\http;

use exceptions\Exception;
use interfaces\domain\ModelInterface;
use interfaces\http\ResponderInterface;
use interfaces\view\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

abstract class Responder implements ResponderInterface
{

    protected $view;
    protected $headers;
    protected $protocolVersion;

    public function __construct(ViewInterface $view,
                                array $headers = [],
                                string $protocolVersion = '1.1')
    {
        $this->view = $view;
        $this->headers = $headers;
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Получить объект ответа
     * @param string $body
     * @param int $status
     * @param array $headers
     * @param string $protocolVersion
     * @return ResponseInterface
     * @throws Exception
     */
    protected function createResponse(string $body,
                                      int $status = 200,
                                      array $headers = [],
                                      string $protocolVersion = '1.1'): ResponseInterface
    {
        return new Response($body, $status, $headers, $protocolVersion);
    }

    /**
     * Сформировать и получить объект ответа
     * @param ModelInterface $model
     * @return ResponseInterface
     * @throws Exception
     */
    public function response(ModelInterface $model): ResponseInterface
    {
        return $this->createResponse(
            $model->draw($this->view),
            $model->hasErrors() ? $model->getErrorCode() : 200,
            $this->headers,
            $this->protocolVersion
        );
    }

    /**
     * Переадресовать запрос
     * @param UriInterface $uri
     * @return ResponseInterface
     * @throws Exception
     */
    public function redirect(UriInterface $uri): ResponseInterface
    {
        return $this->createResponse('')->withHeader('Location', $uri);
    }

}