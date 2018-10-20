<?php

namespace core\domain;

use exceptions\ModelNotFoundException;
use interfaces\domain\ModelInterface;
use interfaces\domain\RepositoryInterface;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use SplSubject;

abstract class Repository implements RepositoryInterface, \SplObserver
{

    /**
     * Найти модель по идентификатору
     * @param int $id
     * @return ModelInterface
     * @throws ModelNotFoundException
     */
    public function findById(int $id): ModelInterface
    {
        $bean = R::load($this->getModelType(), $id);
        if ($bean->isEmpty()){
            throw new ModelNotFoundException('Модель не найдена');
        }
        $model = $this->map($bean);
        $model->attach($this);
        return $model;
    }

    /**
     * Получить тип модели с которой работает репозиторий
     * @return string
     */
    abstract protected function getModelType(): string ;

    /**
     * Преобразовать bean в модель
     * @param OODBBean $bean
     * @return ModelInterface
     */
    abstract protected function map(OODBBean $bean): ModelInterface;

    /**
     * Сохранить состояние модели
     * @param ModelInterface $model
     */
    public function save(ModelInterface $model): void
    {
        $state = $model->createMemento()->getState();
        R::store(R::dispense($state));
    }

    /**
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     * @param SplSubject | ModelInterface $subject <p>
     * The <b>SplSubject</b> notifying the observer of an update.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        $this->save($subject);
    }
}