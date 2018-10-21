<?php

namespace interfaces\domain;

interface MementoInterface
{

    /**
     * Получить идентификатор состояния
     * @return int
     */
    public function getId(): int;

    /**
     * Установить идентификатор состояния
     * @param int $id
     */
    public function setId(int $id);

    /**
     * Получение состояния модели
     * @return array
     */
    public function getState(): array;
}