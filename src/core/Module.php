<?php

namespace core;

use exceptions\ContainerNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Module implements ContainerInterface
{

    /**
     * Модуль родителя
     * @var Module | null
     */
    protected $parent;

    /**
     * Субмодули
     * @var Module[]
     */
    protected $submodules = [];

    /**
     * Контейнер конфигураций
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Триггер прохождения дерева
     * @var array
     */
    protected $notFounded = [];

    /**
     * Module constructor.
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->container = $this->createContainer($this->config());
        $this->addSubmodules($this->config('submodules'));
    }

    /**
     * Конфигурация модуля
     * @param string $name
     * @return array
     * @throws \ReflectionException
     */
    protected function config(string $name = 'main'): array
    {
        $configFile = $this->getPath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $name . '.php';
        $config = file_exists($configFile) ? include_once $configFile : [];
        $splittedName = explode('-', $name);
        $localConfig = [];
        if (!isset($splittedName[1]) or $splittedName[1] !== 'local') {
            $localConfig = $this->config($name . '-local');
        }
        return array_merge($config, $localConfig);
    }

    /**
     * Получение контейнера конфигурации модуля
     * @param array $config
     * @return ContainerInterface
     */
    protected function createContainer(array $config): ContainerInterface
    {
        return new Container($config, $this);
    }

    /**
     * Получить путь к директории модуля
     * @return string
     * @throws \ReflectionException
     */
    public function getPath(): string
    {
        return dirname((new \ReflectionClass($this))->getFileName());
    }

    /**
     * Установка родительского модуля
     * @param Module $module
     */
    public function setParent(Module $module)
    {
        $this->parent = $module;
    }

    /**
     * Добавить субмодуль
     * @param Module $module
     */
    public function addSubmodule(Module $module)
    {
        $module->setParent($this);
        $this->submodules[] = $module;
    }

    /**
     * Добавить субмодули
     * @param Module[] $modules
     */
    public function addSubmodules(array $modules)
    {
        foreach ($modules as $module) {
            $this->addSubmodule($module);
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }
        foreach ($this->submodules as $submodule) {
            if ($submodule->has($id)) {
                return $submodule->get($id);
            }
        }
        if (!empty($this->parent)) {
            return $this->parent->get($id);
        }
        throw new ContainerNotFoundException(sprintf('Сервис "%s" не найден', $id));
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
        return $this->container->has($id);
    }
}