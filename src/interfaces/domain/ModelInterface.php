<?php

namespace interfaces\domain;

use interfaces\view\ViewInterface;

interface ModelInterface extends \SplSubject, \ArrayAccess
{

    /**
     * Получить идентификатор модели
     * @return int
     */
    public function getId(): int;

    /**
     * Получение снимка состояния модели
     * @return MementoInterface
     */
    public function createMemento(): MementoInterface;

    /**
     * Восстановить состояние модели из снимка
     * @param MementoInterface $memento
     * @return void
     */
    public function restoreState(MementoInterface $memento);

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

    /**
     * Есть ли ошибки в модели
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Получить код ошибки
     * @return int
     */
    public function getErrorCode(): int;

    /**
     * Вывод модели в виде строки
     * @param ViewInterface $view
     * @return string
     */
    public function draw(ViewInterface $view): string;

}