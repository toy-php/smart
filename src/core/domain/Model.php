<?php

namespace core\domain;

use core\DataArrayAccessTrait;
use core\EventsTrait;
use core\SubjectTrait;
use exceptions\ErrorException;
use exceptions\ErrorsException;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;
use interfaces\view\ViewInterface;

abstract class Model implements ModelInterface
{

    use SubjectTrait;
    use EventsTrait;
    use DataArrayAccessTrait;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var ErrorsException
     */
    protected $errors;

    public function __construct()
    {
        $this->errors = new ErrorsException();
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
        foreach ($this['errors'] as $exception) {
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
        foreach ($this['errors'] as $exception) {
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
     * Получение данных модели по ключу
     * @param mixed $offset
     * @return mixed|null
     * @throws \exceptions\UnknownPropertyException
     */
    public function offsetGet($offset)
    {
        $getter = 'get' . ucfirst($offset);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return $this->innerOffsetGet($offset);
    }

    /**
     * Установка данных модели
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $isValid = false;
        try{
            $validator = 'validate' . ucfirst($offset);
            if (method_exists($this, $validator)) {
                $this->$validator($value);
                $isValid = true;
            }
        }catch (\Exception $exception) {
            $this->errors[] = new ErrorException(
                $offset,
                $value,
                $exception->getMessage(),
                $exception->getCode() ?: 412,
                $exception);
            return;
        }
        $setter = 'set' . ucfirst($offset);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        if ($isValid){
            $this->innerOffsetSet($offset, $value);
        }
    }

    /**
     * Загрузка данных из массива
     * @param array $data
     */
    public function load(array $data)
    {
        foreach ($data as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function restoreState(MementoInterface $memento)
    {
        $state = $memento->getState();
        $this->id = isset($state['id']) and is_numeric($state['id'])? (int)$state['id'] : $this->id;
        $this->data = $state;
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
     * @inheritdoc
     */
    public function draw(ViewInterface $view): string
    {
        return $view->draw($this);
    }

}