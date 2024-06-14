<?php

namespace OneAuth\Controller;

use OneAuth\DataStore\DataStoreInterface;

abstract class AbstractController
{
    protected $dataStore;

    public function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }
}
