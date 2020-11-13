<?php

namespace Controlla\ConektaCashier;

interface BillableRepositoryInterface
{
    /**
     * Find a BillableInterface implementation by Conekta ID.
     *
     * @param string $conektaId
     *
     * @return \Controlla\ConektaCashier\BillableInterface
     */
    public function find($conektaId);
}
