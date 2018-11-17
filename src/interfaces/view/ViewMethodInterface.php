<?php

namespace interfaces\view;

interface ViewMethodInterface
{

    /**
     * Выполнить функцию расширения
     * @param mixed ...$arguments
     * @return mixed
     */
    public function __invoke(... $arguments);
}