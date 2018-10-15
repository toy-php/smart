<?php

namespace interfaces\view;

interface ViewInterface
{

    /**
     * Получение строкового представления данных
     * @param array $data
     * @return string
     */
    public function draw(array $data): string;
}