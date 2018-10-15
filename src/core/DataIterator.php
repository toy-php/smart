<?php

namespace core;

class DataIterator implements \Iterator
{

    private $position = 0;
    private $storage;

    public function __construct(array $data)
    {
        $this->storage = array_values($data);
        $this->position = 0;
    }

    public function current()
    {
        return $this->storage[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->storage[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

}