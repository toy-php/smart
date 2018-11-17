<?php

namespace core\domain;

use exceptions\Exception;
use interfaces\domain\MementoInterface;
use interfaces\view\ViewInterface;

final class NullModel extends Model
{

    public function __construct()
    {
        parent::__construct(0);
    }

    /**
     * @return MementoInterface
     * @throws Exception
     */
    public function createMemento(): MementoInterface
    {
        throw new Exception('Модель не имеет состояния');
    }

    /**
     * Вывод модели в виде строки
     * @param ViewInterface $view
     * @return string
     */
    public function draw(ViewInterface $view): string
    {
        return $view->draw([]);
    }
}