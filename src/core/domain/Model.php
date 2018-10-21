<?php

namespace core\domain;

use interfaces\domain\EventInterface;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;
use SplObserver;

abstract class Model extends BaseObject implements ModelInterface
{

    /**
     * @var array | callable[]
     */
    protected $listeners = [];

    /**
     * Массив свойств которые не будут затронуты при восстановлении состояния модели
     * @var array
     */
    protected $excludedProperty = [];

    /**
     * @inheritdoc
     */
    public function restoreState(MementoInterface $memento)
    {
        $state = $memento->getState();
        foreach ($state as $key => $value) {
            $property = $this->$key;
            if ($property instanceof Model and is_array($value)) {
                $property->restoreState(new Memento($value));
                continue;
            }
            $this->$key = $value;
        }
    }

    /**
     * Подписка на события модели
     * @param string $eventType
     * @param callable $listener
     * @return void
     */
    public function on(string $eventType, callable $listener)
    {
        $this->listeners[$eventType][spl_object_id((object) $listener)] = $listener;
    }

    /**
     * Отписка от событий модели
     * @param string $eventType
     * @param callable $listener
     * @return void
     */
    public function off(string $eventType, callable $listener)
    {
        unset($this->listeners[$eventType][spl_object_id((object) $listener)]);
    }

    /**
     * Оповестить слушателей события
     * @param EventInterface $event
     */
    protected function trigger(EventInterface $event)
    {
        $eventType = get_class($event);
        $listeners = isset($this->listeners[$eventType]) ? $this->listeners[$eventType] : [];
        foreach ($listeners as $listener) {
            $listener($event);
        }
    }

    /**
     * @var array | SplObserver[]
     */
    protected $observers = [];

    /**
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to attach.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function attach(SplObserver $observer)
    {
        $this->observers[spl_object_id($observer)] = $observer;
    }

    /**
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to detach.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function detach(SplObserver $observer)
    {
        unset($this->observers[spl_object_id($observer)]);
    }

    /**
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     * @return void
     * @since 5.1.0
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
}