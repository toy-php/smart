<?php

namespace core\domain;

use core\DataArrayAccessTrait;
use core\DataCountTrait;
use core\DataFilter;
use core\DataFind;
use core\DataIteratorAggregate;
use exceptions\InvalidArgumentException;
use interfaces\domain\CollectionInterface;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;

class Collection extends Model implements CollectionInterface
{

    use DataCountTrait;
    use DataArrayAccessTrait;
    use DataIteratorAggregate;
    use DataFilter;
    use DataFind;

    /**
     * Массив метаданных
     * @var array
     */
    protected $meta = [];

    /**
     * Тип моделей в коллекции
     * @var string
     */
    protected $type;

    public function __construct(string $type = null)
    {
        parent::__construct(0);
        $this->type = $type;
    }

    public function restoreState(MementoInterface $memento)
    {
        $state = $memento->getState();
        foreach ($state as $offset => $data) {
            $model = $this->offsetGet($offset);
            $model->restoreState(new Memento($data));
        }
    }

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
     * Добавить модель в коллекцию
     * @param mixed $offset
     * @param ModelInterface $value
     * @throws InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof ModelInterface){
            throw new InvalidArgumentException();
        }
        if (!empty($this->type) and !$value instanceof $this->type){
            throw new InvalidArgumentException();
        }
        $this->innerOffsetSet($offset, $value);
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