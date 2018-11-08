<?php

namespace core\view;

use exceptions\Exception;

class Template
{

    /**
     * @var View
     */
    protected $view;

    /**
     * Данные представления
     * @var array
     */
    protected $templateData = [];

    /**
     * Имя макета шаблона
     * @var string
     */
    protected $layoutTemplateName = '';

    /**
     * Данные макета шаблона
     * @var array
     */
    protected $layoutTemplateData = [];

    /**
     * имя открытой секции
     * @var string
     */
    protected $startedSectionName = '';

    /**
     * Секции
     * @var array
     */
    protected $sections = [];

    /**
     * Директория для статических файлоов
     * @var string
     */
    protected $assetsPath = 'assets';

    public function __construct(View $view)
    {
        $this->view = $view;
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
     * Фильтрация строки
     * @param string $string
     * @return string
     */
    public function e(string $string): string
    {
        return filter_var($string, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * Получить содержимое без очистки
     * @param string $name
     * @return null|mixed
     */
    public function raw(string $name)
    {
        return $this->__isset($name) ? $this->templateData[$name] : null;
    }

    /**
     * Получить атрибут
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        $value = $this->raw($name);
        if (is_string($value)) {
            return $this->e($value);
        }
        if (is_array($value)) {
            return filter_var_array($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        return $value;
    }

    /**
     * Наличие атрибута
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->templateData[$name]);
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
        if (!isset($this->sections[$this->startedSectionName])) {
            $this->sections[$this->startedSectionName] = [];
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
        $this->sections[$this->startedSectionName][] = ob_get_contents();
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
        if (isset($this->sections[$name])) {
            $sections = array_reverse($this->sections[$name]);
            unset($this->sections[$name]);
        }
        return implode("\n", $sections);
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Объявление макета шаблона
     * @param $layoutTemplateName
     * @param array $layoutViewData
     */
    public function layout(string $layoutTemplateName, array $layoutViewData = [])
    {
        $this->layoutTemplateName = $layoutTemplateName;
        $this->layoutTemplateData = $layoutViewData;
    }

    /**
     * Вставка представления в текущий шаблон
     * @param string $templateName
     * @param array $viewData
     * @return string
     * @throws \Exception
     */
    public function insert(string $templateName, array $viewData = [])
    {
        return $this->view->makeTemplate()->render($templateName, $viewData);
    }

    /**
     * Загрузка шаблона
     * @param string $templateName
     * @throws Exception
     */
    private function loadTemplateFile(string $templateName)
    {
        $file = $this->view->getTemplatePath($templateName);
        include_once $file;
    }

    /**
     * Рендеринг шаблона
     * @param string $templateName
     * @param array $viewData
     * @return string
     * @throws \Exception
     */
    public function render(string $templateName, array $viewData = []): string
    {
        try {
            ob_start(null, 0,
                PHP_OUTPUT_HANDLER_CLEANABLE |
                PHP_OUTPUT_HANDLER_FLUSHABLE |
                PHP_OUTPUT_HANDLER_REMOVABLE
            );
            $this->templateData = $viewData;
            $this->loadTemplateFile($templateName);
            $content = ob_get_contents();
            ob_end_clean();
            if (!empty($this->layoutTemplateName)) {
                /** @var Template $layout */
                $layout = $this->view->makeTemplate();
                $layout->sections = array_merge($this->sections, ['content' => [$content]]);
                $content = $layout->render($this->layoutTemplateName, $this->layoutTemplateData);
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