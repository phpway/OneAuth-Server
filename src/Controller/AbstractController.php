<?php

namespace OneAuth\Controller;

use OneAuth\DataStore\DataStoreInterface;

abstract class AbstractController
{
    protected $dataStore;
    protected $config;

    public function __construct(DataStoreInterface $dataStore, array $config = [])
    {
        $this->dataStore = $dataStore;
        $this->config = $config;
    }
}
