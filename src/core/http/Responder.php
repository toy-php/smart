<?php

namespace core\http;

use interfaces\http\ResponderInterface;
use interfaces\view\ViewInterface;

abstract class Responder implements ResponderInterface
{

    protected $view;

    public function __construct(ViewInterface $view)
    {
        $this->view = $view;
    }
}