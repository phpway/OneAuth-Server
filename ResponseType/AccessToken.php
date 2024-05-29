<?php

namespace OAuth2\ResponseType;

use Hidehalo\Nanoid\Client as Nanoid;
use OAuth2\DataStore\AccessToken as DataStoreAccessToken;
use OAuth2\DataStore\DataStoreInterface;
use OAuth2\Request\TokenParams;
use OAuth2\Server;
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

    public function handle(TokenParams $params, ResponseInterface $response, $user_id = null): ResponseInterface
    {
        $accessToken = $this->createToken($params, $user_id);
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

    protected function createToken(TokenParams $params, $user_id = null): DataStoreAccessToken
    {
        $tokenLifetime = $this->config['token_lifetime'];
        $data = [
            'access_token' => $this->generateToken(),
            'expires' => date('Y-m-d H:i:s', time() + $tokenLifetime),
            'client_id' => $params->get('client_id'),
            'user_id' => $user_id,
            'scope' => $params->get('scope'),
        ];

        return (new DataStoreAccessToken())->createFromArray($data);
    }

    protected function generateToken()
    {
        return (new Nanoid())->generateId($size = 36);
    }
}
