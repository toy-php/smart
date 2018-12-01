<?php

namespace core;

trait DataFilter
{

    protected $data = [];

    /**
     * Фильтрация данных
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter)
    {
        $data = array_filter($this->data, $filter);
        $instance = clone $this;
        $instance->data = $data;
        return $instance;
    }

}