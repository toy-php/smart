<?php

namespace interfaces\view;

use interfaces\domain\ModelInterface;

interface ViewInterface
{

    /**
     * Получение строкового представления данных
     * @param ModelInterface $model
     * @return string
     */
    public function draw(ModelInterface $model): string;
}