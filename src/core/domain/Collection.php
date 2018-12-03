<?php

namespace core\domain;

use core\DataArrayAccessTrait;
use core\DataCountTrait;
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
        if (!$value instanceof ModelInterface) {
            throw new InvalidArgumentException();
        }
        if (!empty($this->type) and !$value instanceof $this->type) {
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

    /**
     * Фильтрация данных
     * @param callable $filter
     * @return CollectionInterface
     */
    public function filter(callable $filter): CollectionInterface
    {
        $instance = clone $this;
        $instance->data = array_filter($this->data, $filter);
        return $instance;
    }

    /**
     * Поиск коллекции элементов удовлетворяющих условиям
     * @param array $attributes
     * @return CollectionInterface
     */
    public function findAllByAttributes(array $attributes): CollectionInterface
    {
        return $this->filter(function ($item) use ($attributes) {
            $matches = [];
            foreach ($attributes as $key => $value) {
                if (is_array($item) and isset($item[$key]) and $item[$key] === $value) {
                    $matches[] = true;
                    continue;
                }
                if (is_object($item) and isset($item->$key) and $item->$key === $value) {
                    $matches[] = true;
                    continue;
                }
                $matches[] = false;
            }
            return (!empty($matches) and !in_array(false, $matches));
        });
    }

    /**
     * Поиск коллекции элементов удовлетворяющих условию
     * @param string $key
     * @param $value
     * @return CollectionInterface
     */
    public function findAllByAttribute(string $key, $value): CollectionInterface
    {
        return $this->findAllByAttributes([$key => $value]);
    }

    /**
     * Поиск первого элемента удовлетворяющего условию
     * @param string $key
     * @param $value
     * @return ModelInterface
     */
    public function findFirstByAttribute(string $key, $value): ModelInterface
    {
        return $this->findAllByAttribute($key, $value)->first();
    }

    /**
     * Поиск последнего элемента удовлетворяющего условию
     * @param string $key
     * @param $value
     * @return ModelInterface
     */
    public function findLastByAttribute(string $key, $value): ModelInterface
    {
        return $this->findAllByAttribute($key, $value)->last();
    }

    /**
     * Получить срез коллекции
     * @param int $offset
     * @param int|null $length
     * @return CollectionInterface
     */
    public function slice(int $offset, int $length = null): CollectionInterface
    {
        $data = array_slice($this->data, $offset, $length);
        $instance = clone $this;
        $instance->data = $data;
        return $instance;
    }

    /**
     * Получить первый элемент
     * @return ModelInterface
     */
    public function first(): ModelInterface
    {
        return reset($this->data);
    }

    /**
     * Получить последний элемент
     * @return ModelInterface
     */
    public function last(): ModelInterface
    {
        return end($this->data);
    }

    /**
     * Сортировка коллекции, используя пользовательскую функцию для сравнения элементов с сохранением ключей
     * @param callable $callback
     * @return CollectionInterface
     */
    public function sort(callable $callback): CollectionInterface
    {
        $data = $this->data;
        uasort($data, $callback);
        $instance = clone $this;
        $instance->data = $data;
        return $instance;
    }

    /**
     * Возвращает коллекци. с моделями в обратном порядке
     * @return CollectionInterface
     */
    public function reverse(): CollectionInterface
    {
        $instance = clone $this;
        $instance->data = array_reverse($instance->data, true);
        return $instance;
    }

    /**
     * Применяет callback-функцию ко всем моделями коллекции
     * @param callable $callback
     * @return CollectionInterface
     */
    public function map(callable $callback): CollectionInterface
    {
        $instance = clone $this;
        $instance->data = array_map($callback, $instance->data);
        return $instance;
    }

    /**
     * Итеративно коллекцию массив к единственной модели, используя callback-функцию
     * @param callable $callback
     * @return ModelInterface
     */
    public function reduce(callable $callback): ModelInterface
    {
        return array_reduce($this->data, $callback);
    }

}