<?php

namespace interfaces\view;

interface ExtensionInterface
{

    /**
     * Выполнить функцию расширения
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments);
}