<?php

namespace core\domain;

use core\EventsTrait;
use core\SubjectTrait;
use exceptions\ErrorsException;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;

abstract class Model extends BaseObject implements ModelInterface
{

    use SubjectTrait;
    use EventsTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var ErrorsException
     */
    protected $errors;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->errors = new ErrorsException();
    }

    /**
     * @inheritdoc
     */
    public function getErrorCode(): int
    {
        return $this->errors->getCode();
    }

    /**
     * @inheritdoc
     */
    public function hasErrors(): bool
    {
        return $this->errors->hasErrors();
    }

    /**
     * @inheritdoc
     */
    public function restoreState(MementoInterface $memento)
    {
        $state = $memento->getState();
        $this->id = isset($state['id']) ? $state['id'] : $this->id;
    }

    /**
     * Получение идентификатора модели
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}