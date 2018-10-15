<?php

namespace core;

trait DataAccessTrait
{

    protected $data = [];

    /**
     * Получение данных объекта в виде массива
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

}