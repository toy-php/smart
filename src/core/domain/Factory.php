<?php

namespace core\domain;

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

}