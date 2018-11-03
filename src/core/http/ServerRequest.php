<?php

namespace core\http;

use Psr\Http\Message\ServerRequestInterface;
use exceptions\Exception;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    protected $serverParams = [];
    protected $cookieParams = [];
    protected $queryParams = [];
    protected $uploadedFiles = [];
    protected $parsedBody = [];
    protected $attributes = [];

    function __construct(string $method,
                         array $headers,
                         array $get,
                         array $post,
                         array $files,
                         array $cookie,
                         array $server,
                         string $protocolVersion = '1.1')
    {
        $this->queryParams = $get;
        $this->parsedBody = $post;
        $this->uploadedFiles = $files;
        $this->cookieParams = $cookie;
        $this->serverParams = $server;
        parent::__construct($method, $headers, $protocolVersion);
    }

    /**
     * Получить объект URI
     * @return UriInterface
     * @throws Exception
     */
    static public function getUriFromGlobals(): UriInterface
    {
        return (new Uri())->withHost(filter_input(INPUT_SERVER, 'HTTP_HOST'))
            ->withScheme(trim(filter_input(INPUT_SERVER, 'HTTPS')) ? 'https' : 'http')
            ->withPath(\parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI'))['path'])
            ->withPort(filter_input(INPUT_SERVER, 'SERVER_PORT'))
            ->withQuery(filter_input(INPUT_SERVER, 'QUERY_STRING'));
    }

    /**
     * Получить объект запроса
     * @return ServerRequestInterface
     * @throws Exception
     */
    static public function fromGlobals(): ServerRequestInterface
    {
        $body = fopen('php://input', 'r');
        $parsedBody = $_POST;
        $xmlHttpRequest = filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING);
        if (!empty($xmlHttpRequest) and strtolower($xmlHttpRequest) === 'xmlhttprequest') {
            $parsedBody = json_decode(stream_get_contents($body), true);
        }
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $method = (php_sapi_name() === 'cli') ? 'COMMAND' : filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        $files = [];
        foreach ($_FILES as $file) {
            if (!is_uploaded_file($file['tmp_name'])) {
                continue;
            }
            $files[] = new UploadedFile($file);
        }
        return (new static($method, $headers, $_GET, $parsedBody, $files, $_COOKIE, $_SERVER))
            ->withUri(static::getUriFromGlobals())
            ->withBody(new Stream($body));
    }

    /**
     * @inheritdoc
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @inheritdoc
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * @inheritdoc
     */
    public function withCookieParams(array $cookies)
    {
        if ($this->cookieParams === $cookies) {
            return $this;
        }
        $instance = clone $this;
        $instance->cookieParams = $cookies;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @inheritdoc
     */
    public function withQueryParams(array $query)
    {
        if ($this->queryParams === $query) {
            return $this;
        }
        $instance = clone $this;
        $instance->queryParams = $query;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritdoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        if ($this->uploadedFiles === $uploadedFiles) {
            return $this;
        }
        $instance = clone $this;
        $instance->uploadedFiles = $uploadedFiles;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        if ($this->parsedBody === $data) {
            return $this;
        }
        $instance = clone $this;
        $instance->parsedBody = $data;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($name, $default = null)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    /**
     * @inheritdoc
     */
    public function withAttribute($name, $value)
    {
        $instance = clone $this;
        $instance->attributes[$name] = $value;
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function withoutAttribute($name)
    {
        if (false === isset($this->attributes[$name])) {
            return clone $this;
        }
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}
