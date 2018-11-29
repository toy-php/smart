<?php

namespace core\domain;

use core\EventsTrait;
use core\SubjectTrait;
use exceptions\ErrorException;
use exceptions\ErrorsException;
use exceptions\Exception;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;
use interfaces\view\ViewInterface;

/**
 * Class Model
 * @package core\domain
 *
 * @property int $id
 *
 * @property-read ErrorsException $errors
 * @property-read int $errorCode
 *
 */
abstract class Model extends BaseObject implements ModelInterface
{

    use SubjectTrait;
    use EventsTrait;

    protected $id = 0;

    /**
     * @var ErrorsException
     */
    protected $errors;

    public function __construct(int $id)
    {
        $this->errors = new ErrorsException();
        $this->setId($id);
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): ErrorsException
    {
        return $this->errors;
    }

    /**
     * @inheritdoc
     */
    public function getError(string $key): string
    {
        /** @var ErrorException $exception */
        foreach ($this->errors as $exception) {
            if ($exception->getKey() === $key) {
                return $exception->getMessage();
            }
        }
        return '';
    }

    /**
     * @inheritdoc
     */
    public function hasError(string $key): bool
    {
        /** @var ErrorException $exception */
        foreach ($this->errors as $exception) {
            if ($exception->getKey() === $key) {
                return true;
            }
        }
        return false;
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
     * Установка атрибутов модели
     * @param $name
     * @param $value
     * @throws \exceptions\UnknownPropertyException
     */
    public function __set($name, $value): void
    {
        try {
            parent::__set($name, $value);
        } catch (ErrorException $exception) {
            $this->errors[] = $exception;
        }
    }

    /**
     * @inheritdoc
     * @param MementoInterface $memento
     * @throws \exceptions\UnknownPropertyException
     * @throws Exception
     */
    public function restoreState(MementoInterface $memento)
    {
        $state = $memento->getState();
        if (isset($state['id']) and $this->getId() > 0 and $this->getId() !== (int)$state['id']){
            throw new Exception('Неверный идентификатор данных');
        }
        foreach ($state as $key => $value) {
            if ($this->__isset($key)) {
                continue;
            }
            $oldValue = $this->__get($key);
            if ($oldValue instanceof ModelInterface and is_array($value)) {
                $oldValue->restoreState(new Memento($value));
                continue;
            }
            $this->__set($key, $value);
        }
    }

    /**
     * Получение идентификатора модели
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Установка идентификатора модели
     * @param int $id
     */
    public function setId(int $id)
    {
        if ($this->getId() > 0) {
            return;
        }
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function draw(ViewInterface $view): string
    {
        return $view->draw($this);
    }

}