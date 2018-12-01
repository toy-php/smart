<?php

namespace core\domain;

use exceptions\Exception;
use interfaces\domain\ModelInterface;
use RedBeanPHP\OODBBean;

abstract class Factory
{

    /**
     * Создание модели с трансляцией данных из bean в модель
     * @param OODBBean $bean
     * @return ModelInterface
     */
    abstract public function createModel(OODBBean $bean): ModelInterface;

    /**
     * Получить класс модели
     * @return string
     */
    abstract public function getModelClass(): string;

    /**
     * Получить тип модели
     * @return string
     * @throws Exception
     */
    public function getType(): string
    {
        $model = $this->getModelClass();
        if (preg_match_all('/[A-Z][a-z]+/', $model, $matches)) {
            $match = array_shift($matches);
            return implode('_', array_map('strtolower', $match));
        }
        throw new Exception('Неизвестный тип модели');
    }

}