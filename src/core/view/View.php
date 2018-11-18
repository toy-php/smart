<?php

namespace core\view;

use exceptions\Exception;
use exceptions\InvalidArgumentException;
use exceptions\UnknownPropertyException;
use interfaces\view\ViewMethodInterface;
use interfaces\view\ViewInterface;

class View implements ViewInterface
{

    /**
     * Путь к директории шаблона
     * @var string
     */
    protected $templateDir = '';

    /**
     * Имя шаблона текущего представления
     * @var string
     */
    protected $templateName = '';

    /**
     * Расширение файла
     * @var string
     */
    protected $templateExt = '.php';

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
     * View constructor.
     * @param string $templateDir
     * @param string $templateName
     * @param string $templateExt
     */
    public function __construct(string $templateName = '', string $templateDir = '', string $templateExt = '.php')
    {
        $this->templateName = $templateName;
        $this->templateDir = $templateDir ?: __DIR__;
        $this->templateExt = $templateExt;
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
     * Загрузить свойства представления
     * @param array $properties
     */
    public function loadProperties(array $properties)
    {
        $this->properties = $properties;
    }

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
     * Получить экземпляр объекта шаблона
     * @return Template
     */
    public function makeTemplate(): Template
    {
        return new Template($this);
    }

    /**
     * Сгенерировать имя шаблона на основе имени класса модели представления
     * @return string
     */
    protected function createTemplateName(): string
    {
        preg_match_all('/[A-Z][a-z]+/m', get_called_class(), $splitted);
        return strtolower(implode('-', array_filter($splitted[0], function ($value) {
            return $value !== 'Model' and $value !== 'View' and $value !== 'ViewModel';
        })));
    }

    /**
     * Получить имя шаблона
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName ?: $this->createTemplateName();
    }

    /**
     * Рендеринг шаблона
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function draw(array $data = []): string
    {
        return $this->makeTemplate()->render($this->getTemplateName(), $data);
    }

}