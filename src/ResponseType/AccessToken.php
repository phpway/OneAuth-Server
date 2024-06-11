<?php

namespace OneAuth\ResponseType;

use Hidehalo\Nanoid\Client as Nanoid;
use OneAuth\DataStore\AccessToken as DataStoreAccessToken;
use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Request\TokenParams;
use OneAuth\Server;
use Psr\Http\Message\ResponseInterface;

class AccessToken
{
    const TOKEN_LIFETIME_SEC = 60 * 60 * 24; // 24 hours
    const GRANT_TYPE = 'authorization_code';

    protected $dataStore;
    protected $config;

    public function __construct(DataStoreInterface $dataStore, array $config = [])
    {
        $this->dataStore = $dataStore;
        $this->config = $config + [
            'token_lifetime' => static::TOKEN_LIFETIME_SEC,
        ];
    }

    public function handle(TokenParams $params, ResponseInterface $response, array $authorizationCode = null): ResponseInterface
    {
        $accessToken = $this->createToken($params, $authorizationCode);
        $this->dataStore->saveAccessToken($accessToken);

        // return access token and other data as JSON
        $json = [
            'token_type' => 'Bearer',
            'access_token' => $accessToken->get('access_token'),
            'expires_in' => strtotime($accessToken->get('expires')) - time(),
            'scope' => $accessToken->get('scope'),
        ];

        return Server::withJson($response, $json);
    }

    protected function createToken(TokenParams $params, array $authorizationCode = null): DataStoreAccessToken
    {
        $tokenLifetime = $this->config['token_lifetime'];
        $userId = $authorizationCode['user_id'] ?? null;
        $scope = $authorizationCode['scope'] ?? null;
        $data = [
            'access_token' => $this->generateToken(),
            'expires' => date('Y-m-d H:i:s', time() + $tokenLifetime),
            'client_id' => $params->get('client_id'),
            'user_id' => $userId,
            'scope' => $scope,
        ];

        return (new DataStoreAccessToken())->createFromArray($data);
    }

    protected function generateToken()
    {
        return (new Nanoid())->generateId($size = 36);
    }
}
