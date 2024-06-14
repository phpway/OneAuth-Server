<?php

namespace OneAuth\Request;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Data\AbstractValidatedData;
use OneAuth\Model\AccessToken;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class RevokeParams extends AbstractValidatedData
{
    const validationMessages = [
        'invalid_method' => 'Invalid request method. Must be POST',
        'invalid_access_token' => 'Invalid or expired access token',
        'invalid_client_id' => 'Invalid client ID',
    ];

    protected $dataStore;

    protected $fields = [
        'access_token',
        'client_id',
        'all_for_user', // optional
    ];

    protected $request = null;
    protected $validatedToken = null;

    /**
     * Create a new RevokeParams instance. It will automatically parse and validate the given request.
     *
     * @param DataStoreInterface  $dataStore    Data store to check client ID, scope, and redirect URL
     * @param RequestInterface    $request      Authorization request object
     */
    public function __construct(DataStoreInterface $dataStore, RequestInterface $request)
    {
        $this->dataStore = $dataStore;
        $this->parseRequestData();
        $this->validate();
    }

    public function getValidatedToken(): ?AccessToken
    {
        return $this->validatedToken;
    }

    /**
     * Parse the request data and store them in $data.
     *
     * @param RequestInterface $request
     */
    protected function parseRequestData(): void
    {
        $data = $this->request->getParsedBody();
        $this->createFromArray($data);
    }

    /**
     * Validate the request parameters. Populates $validationErrors with any errors found.
     * Any invalid parameters will be cleared from $data.
     */
    protected function validate(): void
    {
        $this->validationErrors = [];

        // request must be POST
        if ($this->request->getMethod() !== 'POST') {
            $this->validationErrors[] = static::validationMessages['invalid_method'];
        }

        $token = $this->get('access_token');
        $clientId = $this->get('client_id');

        // get access token
        $token = AccessToken::createFromTokenValue($token, $this->dataStore);
        if (empty($token)) {
            $this->validationErrors[] = static::validationMessages['invalid_access_token'];
        }

        // check if client ID matches
        if ($token->get('client_id') !== $clientId) {
            $this->validationErrors[] = static::validationMessages['invalid_client_id'];
        }

        // check if token is still valid
        if (!$token->isValid()) {
            $this->validationErrors[] = static::validationMessages['invalid_access_token'];
        }

        // remember the token for later use
        $this->validatedToken = $token;
    }
}
