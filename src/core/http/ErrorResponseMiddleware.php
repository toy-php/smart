<?php

namespace core\http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

class ErrorResponseMiddleware extends Middleware
{

    protected $errorHandler;

    /**
     * Установить логер ошибок
     * @var LoggerInterface|null
     */
    protected $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Установить обработчик ошибки
     * @param HandlerInterface $handler
     */
    public function setErrorHandler(HandlerInterface $handler)
    {
        $this->errorHandler = $handler;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->next->process($request, $handler);
        } catch (\Throwable $exception) {
            ob_start();
            $whoops = new Run;
            $whoops->pushHandler(function (\Throwable $exception) {
                if (!empty($this->logger)) {
                    $this->logger->error($exception->getMessage(), $exception->getTrace());
                }
            });
            if (!empty($this->errorHandler)) {
                $header = $request->getHeader('Accept');
                $contentType = array_shift($header);
                $whoops->pushHandler($this->errorHandler);
            } elseif (Misc::isAjaxRequest()) {
                $whoops->pushHandler(new JsonResponseHandler);
                $contentType = 'application/json';
            } elseif (Misc::isCommandLine()) {
                $whoops->pushHandler(new PlainTextHandler);
                $contentType = 'text/plain';
            } else {
                $whoops->pushHandler(new PrettyPageHandler);
                $contentType = 'text/html';
            }
            $whoops->handleException($exception);
            $content = ob_end_flush();
            return new Response($content, $exception->getCode() ?: 500, [
                'Content-Type' => $contentType
            ]);
        }
    }
}