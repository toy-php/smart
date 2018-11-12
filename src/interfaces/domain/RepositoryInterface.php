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
     * Найти массив моделей согласно фильтру
     * @param array $filter
     * @param int $page
     * @param int $limit
     * @return ModelInterface[]
     * @throws ModelNotFoundException
     */
    public function findList(array $filter = [], int $page = 0, int $limit = 20): array;

    /**
     * Количество моделей удовлетворяющих фильтру
     * @param array $filter
     * @return int
     */
    public function count(array $filter = []): int;

    /**
     * Сохранить состояние модели
     * @param ModelInterface $model
     */
    public function save(ModelInterface $model): void;

}