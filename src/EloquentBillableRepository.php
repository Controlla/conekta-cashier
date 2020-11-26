<?php

namespace Controlla\ConektaCashier;

use Controlla\ConektaCashier\Contracts\Billable as BillableContract;
use Illuminate\Support\Facades\Config;

class EloquentBillableRepository implements BillableRepositoryInterface
{
    /**
     * Find a BillableInterface implementation by Conekta ID.
     *
     * @param string $conektaId
     *
     * @return \Controlla\ConektaCashier\BillableInterface
     */
    public function find($conektaId)
    {
        $model = $this->createCashierModel(Config::get('services.conekta.model'));

        return $model->where($model->getConektaIdName(), $conektaId)->first();
    }

    /**
     * Create a new instance of the Auth model.
     *
     * @param string $model
     *
     * @return \Controlla\ConektaCashier\BillableInterface
     */
    protected function createCashierModel($class)
    {
        $model = new $class();

        if (!$model instanceof BillableContract) {
            throw new \InvalidArgumentException('Model does not implement Billable.');
        }

        return $model;
    }
}
