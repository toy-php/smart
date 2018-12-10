<?php

namespace core;

trait EventsTrait
{

    /**
     * @var array | callable[]
     */
    protected $listeners = [];

    /**
     * Подписка на события
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function on(string $event, callable $listener)
    {
        $this->listeners[$event][spl_object_id((object) $listener)] = $listener;
    }

    /**
     * Подписка на единичное исполнение функции слушателя
     * @param string $event
     * @param callable $listener
     */
    public function once(string $event, callable $listener)
    {
        $onceListener = function ($context) use (&$onceListener, $event, $listener){
            $listener($context);
            $this->off($event, $onceListener);
        };
        $this->on($event, $onceListener);
    }

    /**
     * Отписка от событий
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function off(string $event, callable $listener)
    {
        unset($this->listeners[$event][spl_object_id((object) $listener)]);
    }

    /**
     * Оповестить слушателей события
     * @param string $event
     * @param array $context
     */
    protected function trigger(string $event, array $context = [])
    {
        $listeners = isset($this->listeners[$event]) ? $this->listeners[$event] : [];
        foreach ($listeners as $listener) {
            $listener($context);
        }
    }

}