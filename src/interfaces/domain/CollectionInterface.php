<?php

namespace interfaces\domain;

interface CollectionInterface extends ModelInterface, \Countable, \IteratorAggregate, \ArrayAccess
{

    /**
     * Фильтрация данных
     * @param callable $filter
     * @return CollectionInterface
     */
    public function filter(callable $filter): CollectionInterface;

    /**
     * Поиск коллекции элементов удовлетворяющих условиям
     * @param array $attributes
     * @return CollectionInterface
     */
    public function findAllByAttributes(array $attributes): CollectionInterface;

    /**
     * Поиск коллекции элементов удовлетворяющих условию
     * @param string $key
     * @param $value
     * @return CollectionInterface
     */
    public function findAllByAttribute(string $key, $value): CollectionInterface;

    /**
     * Поиск первого элемента удовлетворяющего условию
     * @param string $key
     * @param $value
     * @return ModelInterface
     */
    public function findFirstByAttribute(string $key, $value): ModelInterface;

    /**
     * Поиск последнего элемента удовлетворяющего условию
     * @param string $key
     * @param $value
     * @return ModelInterface
     */
    public function findLastByAttribute(string $key, $value): ModelInterface;

    /**
     * Получить срез коллекции
     * @param int $offset
     * @param int|null $length
     * @return CollectionInterface
     */
    public function slice(int $offset, int $length = null): CollectionInterface;

    /**
     * Получить первый элемент
     * @return ModelInterface
     */
    public function first(): ModelInterface;

    /**
     * Получить последний элемент
     * @return ModelInterface
     */
    public function last(): ModelInterface;

    /**
     * Сортировка коллекции, используя пользовательскую функцию для сравнения элементов с сохранением ключей
     * @param callable $callback
     * @return CollectionInterface
     */
    public function sort(callable $callback): CollectionInterface;

    /**
     * Возвращает коллекци. с моделями в обратном порядке
     * @return CollectionInterface
     */
    public function reverse(): CollectionInterface;

    /**
     * Применяет callback-функцию ко всем моделями коллекции
     * @param callable $callback
     * @return CollectionInterface
     */
    public function map(callable $callback): CollectionInterface;

    /**
     * Итеративно коллекцию массив к единственной модели, используя callback-функцию
     * @param callable $callback
     * @return ModelInterface
     */
    public function reduce(callable $callback): ModelInterface;
}