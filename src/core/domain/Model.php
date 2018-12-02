<?php

namespace core\domain;

use core\EventsTrait;
use core\SubjectTrait;
use exceptions\ErrorException;
use exceptions\ErrorsException;
use exceptions\Exception;
use exceptions\InvalidArgumentException;
use exceptions\UnknownPropertyException;
use interfaces\domain\MementoInterface;
use interfaces\domain\ModelInterface;
use interfaces\view\ViewInterface;

/**
 * Class Model
 * @package core\domain
 *
 * @property int $id
 *
 * @property int $errorCode
 * @property ErrorsException $errors
 *
 */
abstract class Model extends BaseObject implements ModelInterface
{

    use SubjectTrait;
    use EventsTrait;

    /**
     * @memento false
     * @var \ReflectionClass
     */
    protected $reflection;

    /**
     * Идентификатор модели
     * @memento true
     * @var int
     */
    protected $id = 0;

    /**
     * @memento false
     * @var ErrorsException
     */
    protected $errors;

    protected static $filterTypes = [
        'boolean' => true,
        'bool' => true,
        'integer' => true,
        'int' => true,
        'float' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'callable' => true
    ];

    /**
     * Model constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        try{
            $this->reflection = new \ReflectionClass($this);
        }catch (\ReflectionException $e){
            // исключения не будет
        }
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
     * Парсинг док блока свойства
     * @param $name
     * @return array
     */
    protected function parsePropertyDocBlock($name)
    {
        $property = $this->reflection->getProperty($name);
        $comment = $property->getDocComment();
        if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $comment, $matches)) {
            return array_combine($matches[1], $matches[2]);
        }
        return [];
    }

    /**
     * Проверить тип содержимого
     * @param $name
     * @param $value
     * @return bool
     */
    protected function assertTypeProperty($name, $value): bool
    {
        if (!$this->reflection->hasProperty($name)) {
            return false;
        }
        $property = $this->parsePropertyDocBlock($name);
        $type = $property['var'];
        $type = $type === 'float' ? 'double' : $type;
        $type = $type === 'int' ? 'integer' : $type;
        $type = $type === 'bool' ? 'boolean' : $type;
        if (in_array($type, static::$filterTypes)){
            return gettype($value) === $type;
        }
        return $value instanceof $type;
    }

    /**
     * Является ли свойство доступным для чтения
     * @param $name
     * @return bool
     */
    protected function isReadProperty($name): bool
    {
        if (!$this->reflection->hasProperty($name)) {
            return false;
        }
        $property = $this->parsePropertyDocBlock($name);
        return (isset($property['access']) and ($property['access'] === 'read' or $property['access'] === 'read-write'));
    }

    /**
     * Является ли свойство доступным для записи
     * @param $name
     * @return bool
     */
    protected function isWriteProperty($name): bool
    {
        if (!$this->reflection->hasProperty($name)) {
            return false;
        }
        $property = $this->parsePropertyDocBlock($name);
        return (isset($property['access']) and ($property['access'] === 'write' or $property['access'] === 'read-write'));
    }

    /**
     * Доступно ли свойство для создания снимка состояния
     * @param $name
     * @return bool
     */
    protected function isMemento($name): bool
    {
        if (!$this->reflection->hasProperty($name)) {
            return false;
        }
        $property = $this->parsePropertyDocBlock($name);
        return (isset($property['memento']) and $property['memento'] === 'true');
    }

    /**
     * Установка атрибутов модели
     * @param $name
     * @param $value
     * @throws \exceptions\UnknownPropertyException
     * @throws InvalidArgumentException
     */
    protected function set($name, $value): void
    {
        try {
            parent::set($name, $value);
        } catch (\InvalidArgumentException $exception) {
            $this->errors[] = new ErrorException($name, $value, $exception->getMessage(), 412, $exception);
        } catch (UnknownPropertyException $exception) {
            if ($this->isWriteProperty($name)) {
                if (!$this->assertTypeProperty($name, $value)){
                    throw new InvalidArgumentException(sprintf('Неверный тип данных для свойства "%s" класса "%s"', $name, $this->reflection->getName()));
                }
                $this->$name = $value;
                return;
            }
            throw $exception;
        }
    }

    /**
     * Получить значение свойства
     * @param string $name
     * @return mixed
     * @throws UnknownPropertyException
     */
    protected function get(string $name)
    {
        try {
            return parent::get($name);
        } catch (UnknownPropertyException $exception) {
            if ($this->isReadProperty($name)) {
                return $this->$name;
            }
            throw $exception;
        }
    }

    /**
     * Получение снимка состояния модели
     * @return MementoInterface
     * @throws Exception
     */
    public function createMemento(): MementoInterface
    {
        $state = [];
        $properties = $this->reflection->getProperties();
        foreach ($properties as $property) {
            $name = $property->getName();
            if ($this->__isset($name) and $this->isMemento($name)){
                $value = $this->__get($name);
                if ($value instanceof ModelInterface) {
                    $state[$name] = $value->createMemento()->getState();
                    continue;
                }
                $state[$name] = $value;
            }
        }

        return new Memento($state);
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
        if (isset($state['id']) and $this->getId() > 0 and $this->getId() !== (int)$state['id']) {
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