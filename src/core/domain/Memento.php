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
     * @inheritdoc
     */
    public function getId(): int
    {
        return isset($this->state['id']) ? $this->state['id'] : 0;
    }

    /**
     * @inheritdoc
     */
    public function setId(int $id)
    {
        $this->state['id'] = $id;
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