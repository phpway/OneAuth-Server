<?php
namespace OneAuth\Controller;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Request\RevokeParams;
use OneAuth\Server;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class RevokeController
{
    const validationMessages = [
    ];

    protected $dataStore;

    public function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * Handle request to revoken access token.
     * The request body should have this data:
     *  - access_token:          access token to be revoked
     *  - client_id:             must match cliendId of the token
     *  - all_for_user:          optional, if present, revoke all tokens for the user
     */
    public function handleRevokeRequest(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = new RevokeParams($this->dataStore, $request);
        $validationErrors = $params->getValidationErrors();
        $accessToken = $params->getValidatedToken();

        if (!empty($validationErrors)) {
            return Server::withJson($response, ['error' => implode(', ', $validationErrors)])->withStatus(400);
        }

        if ($params->get('all_for_user')) {
            $accessToken->deleteAllForUser();
        } else {
            $accessToken->delete();
        }

        // always return 200 OK
        return $response->withStatus(200, 'OK');
    }
}
