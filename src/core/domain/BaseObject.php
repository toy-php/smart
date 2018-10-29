<?php

namespace core\domain;

use exceptions\UnknownPropertyException;

class BaseObject
{

    /**
     * Изменение данных объекта по имени с помощью функции сеттера
     * @param $name
     * @param $value
     * @return void
     * @throws UnknownPropertyException
     */
    public function __set($name, $value): void
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
        throw new UnknownPropertyException(sprintf('Объект класса "%s" не содержит метода доступа к свойству "%s"', get_called_class(), $name));
    }

    /**
     * Обнуление данных объекта по имени с помощью функции сеттера
     * @param $name
     * @throws UnknownPropertyException
     */
    public function __unset($name): void
    {
        $this->__set($name, null);
    }

    /**
     * Получение данных объекта по имени с помощью функции геттера
     * @param string $name
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function __get(string $name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        throw new UnknownPropertyException(sprintf('Объект класса "%s" не содержит метода доступа к свойству "%s"', get_called_class(), $name));
    }

    /**
     * Проверка наличия свойств или данных объекта по имени
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        try {
            return $this->__get($name) !== null;
        }catch (UnknownPropertyException $exception){
            return false;
        }
    }

}