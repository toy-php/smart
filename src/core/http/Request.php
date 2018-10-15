<?php

namespace core\http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{

    protected $requestTarget;

    protected $method;

    /**
     * @var UriInterface
     */
    protected $uri;

    public function __construct(string $method, array $headers, string $protocolVersion)
    {
        $this->method = $method;
        parent::__construct($headers, $protocolVersion);
    }

    /**
     * @inheritdoc
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if ($this->uri === null) {
            return '/';
        }

        $target = $this->uri->getPath();

        $query = $this->uri->getQuery();

        if ($query !== '') {
            $target .= '?'.$query;
        }

        $this->requestTarget = $target;

        return $this->requestTarget;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget)
    {
        if ($this->requestTarget = $requestTarget) {
            return $this;
        }
        $instance = clone $this;
        $instance->requestTarget = $requestTarget;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method)
    {
        if ($this->method = $method) {
            return $this;
        }
        $instance = clone $this;
        $instance->method = $method;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $instance = clone $this;
        $instance->uri = $uri;
        if ($preserveHost === true) {
            if($host = $uri->getHost() and !$this->hasHeader('host')){
                $instance->headers = ['host' => [$host]] + $instance->headers;
            }
        }
        return $instance;
    }
}