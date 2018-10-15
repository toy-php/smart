<?php

namespace core\view;

use exceptions\Exception;
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
     * View constructor.
     * @param string $templateDir
     * @param string $templateName
     * @param string $templateExt
     */
    public function __construct(string $templateName = '', string $templateDir = '',  string $templateExt = '.php')
    {
        $this->templateName = $templateName;
        $this->templateDir = $templateDir ?: __DIR__;
        $this->templateExt = $templateExt;
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