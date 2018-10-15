<?php

namespace core\http;

use exceptions\NotFoundHttpException;
use interfaces\http\ActionInterface;
use interfaces\http\RouteInterface;
use interfaces\http\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router implements RouterInterface
{

    /**
     * @var array
     */
    protected $routs;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(array $routs, ContainerInterface $container)
    {
        $this->routs = $routs;
        $this->container = $container;
    }

    /**
     * Разобрать конфигурацию
     * @param array $routs
     * @param string $group
     * @return array
     */
    protected function parseConfig(array $routs, string $group = '')
    {
        $result = [];
        foreach ($routs as $pattern => $action) {
            if (is_array($action)) {
                $result = array_merge($result, $this->parseConfig($action, $group . (is_string($pattern) ? $pattern : '')));
                continue;
            }
            $pattern_array = explode('/', $pattern);
            $method = array_shift($pattern_array);
            $pattern_chunk = '/' . ltrim(implode('/', $pattern_array), '/');
            $result[] = [
                'method' => $method,
                'pattern' => $group . $pattern_chunk,
                'action' => $action
            ];
        }
        return $result;
    }

    /**
     * Получить объект маршрутов
     * @param array $config
     * @return \SplObjectStorage | RouteInterface[]
     */
    protected function createRouts(array $config)
    {
        $routs = new \SplObjectStorage();
        foreach ($config as $route) {
            $routs->attach($this->createRoute($route['method'], $route['pattern']), $route['action']);
        }
        return $routs;
    }

    /**
     * Получить объект маршрута
     * @param string $method
     * @param string $pattern
     * @return RouteInterface
     */
    protected function createRoute(string $method, string $pattern): RouteInterface
    {
        return new Route($method, $pattern);
    }

    /**
     * Найти обработчик запроса
     * @param ServerRequestInterface $request
     * @return ActionInterface
     * @throws NotFoundHttpException
     */
    public function find(ServerRequestInterface $request): ActionInterface
    {
        $config = $this->parseConfig($this->routs);
        $routs = $this->createRouts($config);
        foreach ($routs as $route) {
            if ($route->isMatch($request)) {
                $action = $this->container->get($routs[$route]);
                $action->setArguments($route->getArguments());
                return $action;
            }
        }
        throw new NotFoundHttpException('Маршрут не найден');
    }
}