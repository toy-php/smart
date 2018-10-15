<?php

namespace exceptions;

use core\DataArrayAccessTrait;
use core\DataCountTrait;
use core\DataIteratorAggregate;

class MultiException extends Exception implements \ArrayAccess, \Countable, \IteratorAggregate
{

    use DataArrayAccessTrait;
    use DataCountTrait;
    use DataIteratorAggregate;

}