<?php

namespace core\domain;

use core\DataCountTrait;
use core\DataIteratorAggregate;
use interfaces\domain\CollectionInterface;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;

class Collection extends Model implements CollectionInterface
{

    use DataCountTrait;
    use DataIteratorAggregate;

    /**
     * Массив метаданных
     * @var array
     */
    protected $meta = [];

    /**
     * Получение снимка состояния модели
     * @return MementoInterface
     */
    public function createMemento(): MementoInterface
    {
        $data = [];
        /** @var ModelInterface $model */
        foreach ($this->data as $model) {
            $data[] = $model->createMemento()->getState();
        }
        return new Memento($data);
    }

    /**
     * Получить метаданные
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Установить метаданные
     * @param array $meta
     */
    public function setMeta(array $meta)
    {
        $this->meta = $meta;
    }

}