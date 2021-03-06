<?php

namespace core\view;

use exceptions\Exception;
use exceptions\InvalidArgumentException;
use exceptions\UnknownPropertyException;
use interfaces\domain\ModelInterface;
use interfaces\view\ViewMethodInterface;
use interfaces\view\ViewInterface;

class View implements ViewInterface
{

    /**
     * Функции расширения представления
     * @var array
     */
    protected $methods = [];

    /**
     * Свойства представления
     * @var array
     */
    protected $properties = [];

    /**
     * Имя шаблона текущего представления
     * @var string
     */
    public $templateName = '';

    /**
     * Путь к директории шаблона
     * @var string
     */
    public $templateDir = __DIR__;

    /**
     * Расширение файла
     * @var string
     */
    public $templateExt = '.php';

    /**
     * Директория для статических файлоов
     * @var string
     */
    public $assetsPath = 'assets';

    /**
     * Получить свойство представления
     * @param $name
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function __get($name)
    {
        if (!$this->__isset($name)){
            throw new UnknownPropertyException(sprintf('Представление не имеет свойства "%s"', $name));
        }
        return $this->properties[$name];
    }

    /**
     * Установить свойство представления
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Наличие свойства представления
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Добавление масива функций расширений
     * @param array $methods
     * @throws InvalidArgumentException
     */
    public function addMethods(array $methods)
    {
        foreach ($methods as $name => $method) {
            $this->addMethod($name, $method);
        }
    }

    /**
     * Добавить расширение
     * @param string $name
     * @param ViewMethodInterface $method
     * @throws InvalidArgumentException
     */
    public function addMethod(string $name, ViewMethodInterface $method)
    {
        if (isset($this->methods[$name])){
            throw new InvalidArgumentException(sprintf('Функция с именем "%s" уже зарегистрирована', $name));
        }
        $this->methods[$name] = $method;
    }

    /**
     * Выполнить функцию расширения
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        if (!isset($this->methods[$name])){
            throw new InvalidArgumentException(sprintf('Функция с именем "%s" не зарегистрирована', $name));
        }
        return $this->methods[$name](...$arguments);
    }

    /**
     * Получить путь к файлу шаблона
     * @param string $templateName
     * @return string
     * @throws Exception
     */
    public function getTemplatePath(string $templateName): string
    {
        $templatePath = rtrim($this->templateDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $templateName;
        if (!is_file($templatePath)) {
            $templatePath .= $this->templateExt;
        }
        if (file_exists($templatePath)) {
            return $templatePath;
        }
        throw new Exception(sprintf('Файл шаблона по пути "%s" недоступен', $templatePath));
    }


    /**
     * Получить путь к директории статических файлов
     * @param string $path
     * @param bool $isAppendTimestamp
     * @return string
     * @throws Exception
     */
    public function asset(string $path, bool $isAppendTimestamp = false): string
    {
        $filePath = DIRECTORY_SEPARATOR . $this->assetsPath . DIRECTORY_SEPARATOR .  ltrim($path, DIRECTORY_SEPARATOR);
        $realPath = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') . $filePath;
        if (!file_exists($realPath)) {
            throw new Exception(
                'Файл по пути "' . $realPath . '" не доступен'
            );
        }
        $lastUpdated = filemtime($realPath);
        return $filePath . ($isAppendTimestamp ? '?v=' . $lastUpdated : '');
    }

    /**
     * Получить экземпляр объекта шаблона
     * @return Template
     */
    public function makeTemplate(): Template
    {
        return new Template($this);
    }

    /**
     * Рендеринг шаблона
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function draw(ModelInterface $model): string
    {
        return $this->makeTemplate()->render($this->templateName, $model);
    }

}