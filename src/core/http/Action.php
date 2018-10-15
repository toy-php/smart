<?php

namespace core\http;

use interfaces\http\ActionInterface;
use interfaces\http\ResponderInterface;

abstract class Action implements ActionInterface
{

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var ResponderInterface
     */
    protected $responder;

    public function __construct(ResponderInterface $responder)
    {
        $this->responder = $responder;
    }

    /**
     * Установить найденые совпадения запроса с маршрутом
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

}