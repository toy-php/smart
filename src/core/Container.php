<?php

namespace core;

use exceptions\ContainerException;
use exceptions\ContainerNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    protected $ids = [];
    protected $config = [];
    protected $resolved = [];
    protected $parent;
    protected static $filterTypes = [
        'boolean' => true,
        'bool' => true,
        'integer' => true,
        'int' => true,
        'float' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'callable' => true
    ];


    public function __construct(array $config, ContainerInterface $parent = null)
    {
        $this->ids = array_fill_keys(array_keys($config), true);
        $this->config = $config;
        $this->parent = $parent;
    }

    /**
     * Разрешить зависимость
     * @param $value
     * @return mixed
     * @throws ContainerException
     */
    private function resolve($value)
    {
        $output = $value;
        if (is_string($value)) {
            if ($this->has($value)) {
                $output = $this->get($value);
            } elseif (!empty($this->parent)) {
                $output = $this->parent->get($value);
            }
        }
        return $output;
    }

    /**
     * Получить аргуметы метода
     * @param \ReflectionParameter[] $parameters
     * @param array $configArguments
     * @return array
     * @throws ContainerException
     */
    private function getArguments(array $parameters, array $configArguments)
    {
        $arguments = [];
        foreach ($parameters as $parameter) {
            $position = $parameter->getPosition();
            $parameterType = $parameter->getType();
            $parameterTypeName = $parameterType ? $parameterType->getName() : null;
            if (isset($configArguments[$position])) {
                $arguments[] = (!empty($parameterTypeName) and
                    !isset(static::$filterTypes[$parameterTypeName]))
                    ? $this->resolve($configArguments[$position])
                    : $configArguments[$position];
                continue;
            }
            if (!$parameter->isOptional() and
                !empty($parameterTypeName) and
                !isset(static::$filterTypes[$parameterTypeName])) {
                $arguments[] = $this->resolve($parameterTypeName);
            }
        }
        return $arguments;
    }

    /**
     * Создать объект класса
     * @param \ReflectionClass $reflectionClass
     * @param array $configArguments
     * @return object
     * @throws ContainerException
     */
    private function createInstance(\ReflectionClass $reflectionClass, array $configArguments)
    {
        $classConstructor = $reflectionClass->getConstructor();
        $parameters = $classConstructor ? $classConstructor->getParameters() : [];
        $arguments = $this->getArguments($parameters, $configArguments);
        return $reflectionClass->newInstanceArgs($arguments);
    }

    /**
     * Выполнить методы объекта
     * @param $instance
     * @param \ReflectionClass $reflectionClass
     * @param array $configCalls
     * @throws ContainerException
     */
    private function callMethods($instance, \ReflectionClass $reflectionClass, array $configCalls)
    {
        foreach ($configCalls as $call) {
            if (!$reflectionClass->hasMethod($call['method'])) {
                throw new ContainerException(sprintf('Объект класса "%s" не имеет метода "%s"', $reflectionClass->getName(), $call['method']));
            }
            $method = $reflectionClass->getMethod($call['method']);
            $parameters = $method->getParameters();
            $configArguments = isset($call['arguments']) ? $call['arguments'] : [];
            $arguments = $this->getArguments($parameters, $configArguments);
            $method->invokeArgs($instance, $arguments);
        }
    }

    /**
     * Установить значения свойств объекта
     * @param $instance
     * @param \ReflectionClass $reflectionClass
     * @param array $configProperties
     * @throws ContainerException
     */
    private function setProperties($instance, \ReflectionClass $reflectionClass, array $configProperties)
    {
        foreach ($configProperties as $name => $value) {
            if (!$reflectionClass->hasProperty($name)) {
                throw new ContainerException(sprintf('Объект класса "%s" не имеет свойства "%s"', $reflectionClass->getName(), $name));
            }
            $reflectionClass->getProperty($name)->setValue($instance, $value);
        }
    }

    /**
     * Билдинг объекта
     * @param $instance
     * @param $reflectionClass
     * @param $config
     * @return mixed
     * @throws ContainerException
     */
    private function build($instance, $reflectionClass, $config)
    {
        if (isset($config['properties'])) {
            $this->setProperties($instance, $reflectionClass, $config['properties']);
        }
        if (isset($config['calls'])) {
            $this->callMethods($instance, $reflectionClass, $config['calls']);
        }
        return $instance;
    }

    /**
     * Расширение объекта
     * @param array $config
     * @return mixed
     * @throws ContainerException
     */
    private function extendsObject(array $config)
    {
        try {
            $instance = $this->factory($config['extends']);
            $reflectionClass = new \ReflectionClass($instance);
            return $this->build($instance, $reflectionClass, $config);
        } catch (\ReflectionException $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Создание объекта
     * @param array $config
     * @return mixed
     * @throws ContainerException
     */
    private function createObject(array $config)
    {
        try {
            if (!class_exists($config['class'])) {
                throw new ContainerException(sprintf('Класс "%s" не доступен', $config['class']));
            }
            $reflectionClass = new \ReflectionClass($config['class']);
            $configArguments = isset($config['arguments']) ? $config['arguments'] : [];
            $instance = $this->createInstance($reflectionClass, $configArguments);
            return $this->build($instance, $reflectionClass, $config);
        } catch (\ReflectionException $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Создание объекта
     * @param $id
     * @return object
     * @throws ContainerException
     */
    private function factory($id)
    {
        $config = $this->config[$id];
        if (!is_array($config)) {
            return $config;
        }
        if (!isset($config['class'])){
            if (isset($config['extends'])) {
                return $this->extendsObject($config);
            }
            return $config;
        }
        return $this->createObject($config);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!isset($this->ids[$id])) {
            throw new ContainerNotFoundException(sprintf('Сервис "%s" не найден', $id));
        }
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }
        return $this->resolved[$id] = $this->factory($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        if (isset($this->ids[$id])) {
            return true;
        }
        return false;
    }
}