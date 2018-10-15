<?php

namespace core;

use core\http\Router;
use core\http\ServerRequest;
use exceptions\Exception;
use interfaces\http\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;

class Application extends Module
{

    protected $router;

    /**
     * Application constructor.
     * @throws \ReflectionException
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->router = $this->createRouter($this->config('routs'));
    }

    /**
     * Запуск приложения
     */
    public function run()
    {
        $request = $this->buildServerRequest();
        $handler = $this->router->find($request);
        $response = $this->get(MiddlewareInterface::class)->process($request, $handler);
        $this->respond($response);
    }

    /**
     * @inheritdoc
     * @throws \ReflectionException
     * @throws Exception
     */
    protected function config(string $name = 'main'): array
    {
        if ($name === 'main'){
            return array_merge([
                ServerRequestInterface::class => ServerRequest::fromGlobals(),
                UriInterface::class => ServerRequest::getUriFromGlobals()
            ], parent::config($name));
        }
        return parent::config($name);
    }

    /**
     * Получить объект маршрутизатора
     * @param array $routs
     * @return RouterInterface
     */
    protected function createRouter(array $routs): RouterInterface
    {
        return new Router($routs, $this);
    }

    /**
     * Отправка ответа
     * @param ResponseInterface $response
     */
    protected function respond(ResponseInterface $response)
    {
        if (!headers_sent() and !(php_sapi_name() === 'cli')) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
        $file = fopen('php://output', 'w');
        fwrite($file, $response->getBody());
    }

    /**
     * Получить объект запроса к серверу
     * @return ServerRequestInterface
     */
    protected function buildServerRequest(): ServerRequestInterface
    {
        $request = $this->get(ServerRequestInterface::class);
        if (php_sapi_name() === 'cli') {
            $_argv = $request->getServerParams()['argv'];
            $short = array_map(function ($arg) {
                return trim($arg, '-') . ':';
            }, array_filter($_argv, function ($arg) {
                return preg_match('#^\-(\-)*?[a-z\-_0-9]#is', $arg);
            }));
            $longOpts = array_map(function ($arg) {
                return trim($arg, '-') . ':';
            }, array_filter($_argv, function ($arg) {
                return preg_match('#^\-(\-)*?[a-z\-_0-9]{2,}#is', $arg);
            }));
            $shortOpts = implode('', array_values($short));
            $options = getopt($shortOpts, $longOpts);
            foreach ($options as $name => $option) {
                $request = $request->withAttribute($name, $option);
            }
            $path = '/' . ltrim($request->getAttribute('p', $request->getAttribute('path')), '/');
            $uri = $request->getUri()->withPath($path);
            return $request->withMethod('command')->withUri($uri);
        }
        return $request;
    }
}