<?php

namespace OneAuth\DataStore;

use OneAuth\Data\AbstractData;

class AccessToken extends AbstractData
{
    protected $fields = [
        'access_token',
        'client_id',
        'user_id',
        'expires',
        'scope',
    ];
}
