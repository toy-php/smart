<?php

namespace exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundException extends Exception implements NotFoundExceptionInterface
{

}