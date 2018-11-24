<?php

namespace interfaces\domain;

interface MementoInterface
{

    /**
     * Получение состояния модели
     * @return array
     */
    public function getState(): array;
}