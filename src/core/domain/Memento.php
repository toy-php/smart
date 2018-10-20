<?php

namespace core\domain;

use interfaces\domain\MementoInterface;

class Memento implements MementoInterface
{

    private $state = [];

    /**
     * Memento constructor.
     * @param array $state
     */
    public function __construct(array $state)
    {
        $this->state = $state;
    }

    /**
     * Получение состояния модели
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }

}