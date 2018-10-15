<?php

namespace core\http;

use interfaces\http\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

class Route implements RouteInterface
{

    protected $shortcuts = [
        ':d}' => ':[0-9]++}',             // digit only
        ':l}' => ':[a-z]++}',             // lower case
        ':u}' => ':[A-Z]++}',             // upper case
        ':a}' => ':[0-9a-zA-Z]++}',       // alphanumeric
        ':c}' => ':[0-9a-zA-Z+_\-\.]++}', // common chars
        ':nd}' => ':[^0-9/]++}',           // not digits
        ':xd}' => ':[^0-9/][^/]*+}',       // no leading digits
    ];

    protected $matchGroupName = "\s*([a-zA-Z][a-zA-Z0-9_]*)\s*";

    protected $matchGroupType = ":\s*([^{}]*(?:\{(?-1)\}[^{}]*)*)";

    protected $matchSegment = "[^/]++";

    protected $suffix = '(.html|\/)*?';

    protected $pattern = '';

    protected $method = '';

    protected $arguments = [];

    public function __construct(string $method, string $pattern)
    {
        $this->method = $method;
        $this->pattern = $pattern;
    }

    /**
     * Конвертация шаблона в регулярное выражение
     * @param string $pattern
     * @return string
     */
    protected function convertToRegex(string $pattern)
    {
        $ph = sprintf("\{%s(?:%s)?\}", $this->matchGroupName, $this->matchGroupType);
        $result = preg_replace(
            [
                '~\{' . $this->matchGroupName . '\}~x',
                '~' . $ph . '~x',
            ],
            [
                '{\\1:' . $this->matchSegment . '}',
                '(?<${1}>${2})'
            ],
            strtr($pattern, $this->shortcuts)
        );
        return '#^' . $result . $this->suffix . '$#u';
    }

    /**
     * @inheritdoc
     */
    public function isMatch(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $regex = $this->convertToRegex($this->pattern);
        if (strnatcasecmp($method, $this->method) === 0
            and (bool)preg_match($regex, $path, $arguments)) {
            array_shift($arguments);
            $this->arguments = $arguments;
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}