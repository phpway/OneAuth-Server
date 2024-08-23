<?php
namespace OneAuth\Controller;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Model\AccessToken;
use OneAuth\Server;
use OneAuth\TokenType\Bearer;
use OneAuth\TokenType\TokenTypeInterface;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class ResourceController extends AbstractController
{
    protected $tokenType;

    public function __construct(DataStoreInterface $dataStore, TokenTypeInterface $tokenType = null)
    {
        parent::__construct($dataStore);

        // If no token type is provided, use the default Bearer token type
        if ($tokenType === null) {
            $tokenType = new Bearer;
        }
        $this->tokenType = $tokenType;
    }

    public function verifyResourceRequest(RequestInterface $request, ResponseInterface $response, string $scope = null): ResponseInterface
    {
        $token = $this->tokenType->getAccessToken($request);

        if ($token === null) {
            return Server::withJson($response, ['error' => 'invalid_request', 'error_description' => 'Missing access token'])->withStatus(401);
        }

        $token = AccessToken::createFromTokenValue($token, $this->dataStore);

        if ($token === null || !$token->isValid()) {
            return Server::withJson($response, ['error' => 'invalid_token', 'error_description' => 'Token is invalid or expired'])->withStatus(401);
        }

        // if scope is requested, then check if the token has the required scope
        if ($scope !== null && !$token->checkScope($scope)) {
            return Server::withJson($response, ['error' => 'insufficient_scope', 'error_description' => 'Token does not have the required scope'])->withStatus(403);
        }

        // return userId in response json
        $response->getBody()->write(json_encode(['userId' => $token->getUserId()], JSON_UNESCAPED_UNICODE));
        return $response->withStatus(200);
    }
}
