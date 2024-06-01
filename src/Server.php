<?php
namespace OneAuth;

use OneAuth\Controller\AuthorizeController;
use OneAuth\Controller\TokenController;
use OneAuth\DataStore\DataStoreInterface;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class Server
{
    protected $dataStore;
    protected $authorizeController;
    protected $tokenController;

    public function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    protected function getAuthorizeController()
    {
        if ($this->authorizeController === null) {
            $this->authorizeController = new AuthorizeController($this->dataStore);
        }
        return $this->authorizeController;
    }

    protected function getTokenController()
    {
        if ($this->tokenController === null) {
            $this->tokenController = new TokenController($this->dataStore);
        }
        return $this->tokenController;
    }

    public static function withRedirect(ResponseInterface $response, string $url): ResponseInterface
    {
        return $response->withStatus(302)->withAddedHeader('Location', $url);
    }

    public static function withJson(ResponseInterface $response, array $data): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withAddedHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request to authorize the client.
     *
     * @param RequestInterface  $request        The request to authorize the client
     * @param ResponseInterface $response       The response to be emitted by the server
     * @param bool              $is_authorized  Whether the client is authorized
     * @param mixed             $user_id        The user ID to associate with the token
     *
     * @return ResponseInterface    Response to be emitted by the server
     */
    public function handleAuthorizeRequest(
        RequestInterface $request,
        ResponseInterface $response,
        bool $is_authorized,
        $user_id = null
    ): ResponseInterface {
        return $this->getAuthorizeController()->handleAuthorizeRequest($request, $response, $is_authorized, $user_id);
    }

    /**
     * Handle the request to obtain an authorization token.
     *
     * @param RequestInterface  $request    The request to obtain an authorization token
     * @param ResponseInterface $response   The response to be emitted by the server
     * @param mixed             $user_id    The user ID to associate with the token
     *
     * @return ResponseInterface    Response to be emitted by the server
     */
    public function handleTokenRequest(
        RequestInterface $request,
        ResponseInterface $response,
        $user_id = null
    ): ResponseInterface {
        return $this->getTokenController()->handleTokenRequest($request, $response, $user_id);
    }

    /**
     * Validate the request to authorize the client.
     */
    public function validateAuthorizeRequest(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->getAuthorizeController()->validateRequest($request, $response);
    }

    public function isAuthorizeRequestValid(RequestInterface $request, ResponseInterface $response): bool
    {
        return $this->validateAuthorizeRequest($request, $response)->getStatusCode() === 200;
    }
}
