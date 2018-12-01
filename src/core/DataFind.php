<?php

namespace core;

trait DataFind
{

    protected $data = [];

    /**
     * Поиск первого элемента удовлетворяющего условию
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function findFirst(string $key, $value)
    {
        $data = array_filter($this->data, function ($item) use($key, $value){
            if (is_array($item) and isset($item[$key]) and $item[$key] === $value) {
                return true;
            }
            if (is_object($item) and isset($item->$key) and $item->$key === $value){
                return true;
            }
            return false;
        });
        return array_shift($data);
    }

}