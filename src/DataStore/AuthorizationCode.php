<?php

namespace OneAuth\DataStore;

use OneAuth\Data\AbstractData;

class AuthorizationCode extends AbstractData
{
    protected $fields = [
        'authorization_code',
        'client_id',
        'user_id',
        'redirect_url',
        'expires',
        'scope',
        'state',
        'code_challenge',
        'code_challenge_method',
    ];
}
