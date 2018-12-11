<?php

namespace core\view;

use exceptions\Exception;
use interfaces\domain\ModelInterface;

class Template
{

    /**
     * @var View
     */
    protected $view;

    /**
     * Модель данных представления
     * @var ModelInterface
     */
    protected $model;

    /**
     * Имя макета шаблона
     * @var string
     */
    protected $layoutTemplateName = '';

    /**
     * Модель данных макета шаблона
     * @var ModelInterface
     */
    protected $layoutModel;

    /**
     * имя открытой секции
     * @var string
     */
    protected $startedSectionName = '';

    /**
     * Секции
     * @var array
     */
    protected static $sections = [];

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Получить атрибут
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->__isset($name) ? $this->model->$name : null;
    }

    /**
     * Наличие атрибута
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->model->$name);
    }

    /**
     * Старт секции
     * @param $name
     * @throws Exception
     */
    public function start($name)
    {
        if ($name === 'content') {
            throw new Exception('Секция с именем "content" зарезервированна.');
        }
        $this->startedSectionName = $name;
        if (!isset(static::$sections[$this->startedSectionName])) {
            static::$sections[$this->startedSectionName] = [];
        }
        ob_start(null, 0,
            PHP_OUTPUT_HANDLER_CLEANABLE |
            PHP_OUTPUT_HANDLER_FLUSHABLE |
            PHP_OUTPUT_HANDLER_REMOVABLE
        );
    }

    /**
     * Стоп секции
     * @throws Exception
     */
    public function stop()
    {
        if (empty($this->startedSectionName)) {
            throw new Exception('Сперва нужно стартовать секцию методом start()');
        }
        static::$sections[$this->startedSectionName][] = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Вывод секции
     * @param string $name
     * @return string|null;
     */
    public function section($name)
    {
        $sections = [];
        if (isset(static::$sections[$name])) {
            $sections = array_reverse(static::$sections[$name]);
            unset(static::$sections[$name]);
        }
        return implode("\n", $sections);
    }

    /**
     * Объявление макета шаблона
     * @param $layoutTemplateName
     * @param ModelInterface $layoutModel
     */
    public function layout(string $layoutTemplateName, ModelInterface $layoutModel = null)
    {
        $this->layoutTemplateName = $layoutTemplateName;
        $this->layoutModel = $layoutModel ?: $this->model;
    }

    /**
     * Вставка представления в текущий шаблон
     * @param string $templateName
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function insert(string $templateName, ModelInterface $model = null)
    {
        return $this->view->makeTemplate()->render($templateName, $model ?: $this->model);
    }

    /**
     * Загрузка шаблона
     * @param string $templateName
     * @throws Exception
     */
    private function loadTemplateFile(string $templateName)
    {
        $file = $this->view->getTemplatePath($templateName);
        include $file;
    }

    /**
     * Рендеринг шаблона
     * @param string $templateName
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function render(string $templateName, ModelInterface $model): string
    {
        try {
            ob_start(null, 0,
                PHP_OUTPUT_HANDLER_CLEANABLE |
                PHP_OUTPUT_HANDLER_FLUSHABLE |
                PHP_OUTPUT_HANDLER_REMOVABLE
            );
            $this->model = $model;
            $this->loadTemplateFile($templateName);
            $content = ob_get_clean();
            if (!empty($this->layoutTemplateName)) {
                /** @var Template $layout */
                $layout = $this->view->makeTemplate();
                static::$sections['content'][] = $content;
                $content = $layout->render($this->layoutTemplateName, $this->layoutModel);
            }
            return $content;
        } catch (\Exception $exception) {
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            throw $exception;
        }
    }
}