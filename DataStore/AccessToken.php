<?php

namespace OAuth2\DataStore;

use OAuth2\Data\AbstractData;

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
