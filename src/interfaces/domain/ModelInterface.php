<?php

namespace interfaces\domain;

interface ModelInterface extends \SplSubject
{

    /**
     * Получение снимка состояния модели
     * @return MementoInterface
     */
    public function createMemento(): MementoInterface;

    /**
     * Подписка на события модели
     * @param string $eventType
     * @param callable $listener
     * @return void
     */
    public function on(string $eventType, callable $listener);

    /**
     * Отписка от событий модели
     * @param string $eventType
     * @param callable $listener
     * @return void
     */
    public function off(string $eventType, callable $listener);

}