<?php

namespace OneAuth\ResponseType;

use Hidehalo\Nanoid\Client as Nanoid;
use OneAuth\DataStore\AuthorizationCode as DataStoreAuthorizationCode;
use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Request\AuthorizeParams;
use OneAuth\Server;
use Psr\Http\Message\ResponseInterface;

class AuthorizationCode
{
    const AUTHORIZATION_CODE_LIFETIME_SEC = 60;
    const RESPONSE_TYPE = 'code';

    protected $dataStore;
    protected $config;

    public function __construct(DataStoreInterface $dataStore, array $config = [])
    {
        $this->dataStore = $dataStore;
        $this->config = $config + [
            'authorization_code_lifetime' => static::AUTHORIZATION_CODE_LIFETIME_SEC,
        ];
    }

    public function handle(AuthorizeParams $params, ResponseInterface $response, $user_id = null): ResponseInterface
    {
        $authCode = $this->createAuthorizationCode($params, $user_id);
        $this->dataStore->saveAuthorizationCode($authCode);

        // redirect to provided redirect_url with code and state
        $redirectUrl = $authCode->get('redirect_url');
        $code = $authCode->get('authorization_code');
        $state = $authCode->get('state');
        return Server::withRedirect(
            $response,
            $redirectUrl . '?code=' . $code . ($state ? '&state=' . $state : '')
        );
    }

    protected function createAuthorizationCode(AuthorizeParams $params, $user_id = null): DataStoreAuthorizationCode
    {
        $codeLifetime = $this->config['authorization_code_lifetime'];
        $data = $params->getData() + [
            'authorization_code' => $this->generateAuthorizationCode(),
            'user_id' => $user_id,
            'expires' => date('Y-m-d H:i:s', time() + $codeLifetime),
        ];

        return (new DataStoreAuthorizationCode())->createFromArray($data);
    }

    protected function generateAuthorizationCode()
    {
        return (new Nanoid())->generateId();
    }
}
