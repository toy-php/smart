<?php

namespace core\domain;

use exceptions\Exception;
use exceptions\ModelNotFoundException;
use interfaces\domain\CollectionInterface;
use interfaces\domain\ModelInterface;
use interfaces\domain\RepositoryInterface;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use SplSubject;

abstract class Repository implements RepositoryInterface
{

    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Создание модели из данных bean
     * @param OODBBean $bean
     * @return ModelInterface
     * @throws Exception
     * @throws ModelNotFoundException
     */
    protected function createFrom(OODBBean $bean): ModelInterface
    {
        if ($bean->isEmpty()){
            throw new ModelNotFoundException('Модель не найдена');
        }
        $model = $this->factory->createModel($bean);
        $model->attach($this);
        return $model;
    }

    /**
     * Найти модель по идентификатору
     * @param int $id
     * @return ModelInterface
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public function findById(int $id): ModelInterface
    {
        $bean = R::load($this->factory->getType(), $id);
        return $this->createFrom($bean);
    }

    /**
     * Получить объект коллекции
     * @return CollectionInterface
     * @throws Exception
     */
    protected function createCollection(): CollectionInterface
    {
        return new Collection($this->factory->getModelClass());
    }

    /**
     * Найти массив моделей согласно фильтру
     * @param array $filter
     * @param int $page
     * @param int $limit
     * @return ModelInterface[] | CollectionInterface
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public function findList(array $filter = [], int $page = 0, int $limit = 20): CollectionInterface
    {
        $sql = implode(' AND ', array_map(function ($key) {
            return $key . '=?';
        }, array_keys($filter)));
        $offset = $page * $limit;
        $sql .= sprintf(' LIMIT %d, %d', $offset, $limit);
        $beans = R::findAll($this->factory->getType(), $sql, array_values($filter));
        $total = ceil($this->count($filter) / $limit);
        $collection = $this->createCollection();
        foreach ($beans as $bean) {
            $collection[] = $this->createFrom($bean);
        }
        $collection->setMeta([
            'currentPage' => $page,
            'totalPages' => $total,
            'rowsOnPage' => $limit
        ]);
        return $collection;
    }

    /**
     * Количество моделей удовлетворяющих фильтру
     * @param array $filter
     * @return int
     * @throws Exception
     */
    public function count(array $filter = []): int
    {
        $sql = implode(' AND ', array_map(function ($key) {
            return $key . '=?';
        }, array_keys($filter)));
        return R::count($this->factory->getType(), $sql, array_values($filter));
    }

    /**
     * Сохранить состояние модели
     * @param ModelInterface $model
     * @throws \Exception
     */
    public function save(ModelInterface $model): void
    {
        R::begin();
        try{
            if ($model->hasErrors()){
                throw new Exception('Модель имеет ошибки и не может быть сохранена');
            }
            $memento = $model->createMemento();
            $state = $memento->getState();
            $model->id = R::store(R::dispense($state));
            R::commit();
        }
        catch( \Exception $e ) {
            R::rollback();
            throw $e;
        }
    }

    /**
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     * @param SplSubject | ModelInterface $subject <p>
     * The <b>SplSubject</b> notifying the observer of an update.
     * </p>
     * @return void
     * @since 5.1.0
     * @throws \Exception
     */
    public function update(SplSubject $subject)
    {
        $this->save($subject);
    }
}