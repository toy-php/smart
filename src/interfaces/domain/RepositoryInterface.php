<?php

namespace interfaces\domain;

use exceptions\ModelNotFoundException;

interface RepositoryInterface
{

    /**
     * Найти модель по идентификатору
     * @param int $id
     * @return ModelInterface
     * @throws ModelNotFoundException
     */
    public function findById(int $id): ModelInterface;

    /**
     * Сохранить состояние модели
     * @param ModelInterface $model
     */
    public function save(ModelInterface $model): void;

}