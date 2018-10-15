<?php

namespace core\http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use exceptions\Exception;

class Message implements MessageInterface
{

    protected $protocolVersion;
    protected static $availableProtocolVersion = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];
    protected $headers = [];
    protected $body;

    public function __construct(array $headers, string $protocolVersion)
    {
        $this->setHeaders($headers);
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function withProtocolVersion($version)
    {
        if(!isset(self::$availableProtocolVersion[$version])){
            throw new Exception('Неверное значение версии протокола');
        }
        if($this->protocolVersion === $version){
            return $this;
        }
        $instance = clone $this;
        $instance->protocolVersion = $version;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name)
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name)
    {
        $header = strtolower($name);
        return isset($this->headers[$header]) ? $this->headers[$header] : [];
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value)
    {
        $instance = clone $this;
        $instance->setHeader($name, $value);
        return $instance;
    }

    private function setHeader($name, $value)
    {
        $name = strtolower($name);
        if (!is_array($value)) {
            $this->headers[$name] = [trim($value)];
        } else {
            foreach ($value as $k => $v) {
                $this->headers[$name][$k] = trim($v);
            }
        }
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            if (is_array($value)){
                $this->setHeaders($value);
            }
            $this->setHeader($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }
        $instance = clone $this;
        $instance->headers[strtolower($name)][] = $value;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $instance = clone $this;
        $header = strtolower($name);
        unset($instance->headers[$header]);
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $body)
    {
        if($this->body === $body){
           return $this;
        }
        $instance = clone $this;
        $instance->body = $body;
        return $instance;
    }
}