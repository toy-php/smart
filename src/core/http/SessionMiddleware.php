<?php

namespace core\http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware extends Middleware
{

    protected $secret;
    protected $expire;
    protected $digestAlgo;
    protected $cipherAlgo;
    protected $cipherKeyLen;
    public $lifeTime = 0; // Время жизни сессионной куки в днях

    public function __construct(string $secret,
                                int $expire = 2592000,
                                string $digestAlgo = "sha256",
                                string $cipherAlgo = "aes-256-ctr",
                                int $cipherKeyLen = 32)
    {
        $this->secret = $secret;
        $this->expire = $expire;
        $this->digestAlgo = $digestAlgo;
        $this->cipherAlgo = $cipherAlgo;
        $this->cipherKeyLen = $cipherKeyLen;
    }

    /**
     * Получить объект обработчика сессии
     * @return \SessionHandlerInterface
     * @throws \exceptions\SessionException
     */
    protected function createSessionHandler(): \SessionHandlerInterface
    {
        return new CryptoCookieSessionHandler(
            $this->secret,
            $this->expire,
            $this->digestAlgo,
            $this->cipherAlgo,
            $this->cipherKeyLen
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \exceptions\SessionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        session_set_save_handler($this->createSessionHandler(), true);
        if ($this->lifeTime > 0){
            $lifetime = 60 * 60 * 24 * $this->lifeTime;
            session_set_cookie_params($lifetime, '/', $request->getUri()->getHost());
        }
        session_start();
        return $this->next->process($request, $handler);
    }

}