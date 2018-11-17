<?php

namespace core;

use interfaces\domain\EventInterface;

trait EventsTrait
{

    /**
     * @var array | callable[]
     */
    protected $listeners = [];

    /**
     * Подписка на события
     * @param string $eventType
     * @param callable $listener
     * @return void
     */
    public function on(string $eventType, callable $listener)
    {
        $this->listeners[$eventType][spl_object_id((object) $listener)] = $listener;
    }

    /**
     * Отписка от событий
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

}