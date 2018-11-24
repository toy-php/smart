<?php

namespace core\view;

use exceptions\Exception;
use exceptions\InvalidArgumentException;
use interfaces\domain\ModelInterface;
use interfaces\view\ViewMethodInterface;
use interfaces\view\ViewInterface;

class View implements ViewInterface
{

    /**
     * Имя шаблона текущего представления
     * @var string
     */
    protected $templateName = '';

    /**
     * Функции расширения представления
     * @var array
     */
    protected $methods = [];

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
     * View constructor.
     * @param string $templateDir
     * @param string $templateName
     * @param string $templateExt
     */
    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;
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