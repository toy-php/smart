<?php

namespace core\view;

use exceptions\Exception;
use exceptions\InvalidArgumentException;
use interfaces\view\ExtensionInterface;
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
    protected $extensions = [];

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
     * @param array $extensions
     * @throws InvalidArgumentException
     */
    public function addExtensions(array $extensions)
    {
        foreach ($extensions as $name => $extension) {
            $this->addExtension($name, $extension);
        }
    }

    /**
     * Добавить расширение
     * @param string $name
     * @param ExtensionInterface $extension
     * @throws InvalidArgumentException
     */
    public function addExtension(string $name, ExtensionInterface $extension)
    {
        if (isset($this->extensions[$name])){
            throw new InvalidArgumentException(sprintf('Функция с именем "%s" уже зарегистрирована', $name));
        }
        $this->extensions[$name] = $extension;
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
        if (!isset($this->extensions[$name])){
            throw new InvalidArgumentException(sprintf('Функция с именем "%s" не зарегистрирована', $name));
        }
        return $this->extensions[$name](...$arguments);
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