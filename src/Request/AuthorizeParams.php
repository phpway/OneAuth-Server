<?php

namespace OneAuth\Request;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Data\AbstractData;
use OneAuth\ResponseType\AuthorizationCode;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class AuthorizeParams extends AbstractData
{
    const validationMessages = [
        'invalid_response_type' => 'Invalid response type',
        'invalid_client_id' => 'Invalid client ID',
        'invalid_redirect_url' => 'Invalid redirect URL',
        'invalid_scope' => 'Invalid scope',
        'invalid_code_challenge_method' => 'Invalid code challenge method',
    ];

    protected $dataStore;

    protected $fields = [
        'response_type',
        'client_id',
        'redirect_url',
        'scope',
        'state',
        'code_challenge',
        'code_challenge_method',
    ];

    protected $validationErrors = [];

    /**
     * Create a new AuthorizeParams instance. It will automatically parse and validate the given request.
     *
     * @param DataStoreInterface  $dataStore    Data store to check client ID, scope, and redirect URL
     * @param RequestInterface    $request      Authorization request object
     */
    public function __construct(DataStoreInterface $dataStore, RequestInterface $request)
    {
        $this->dataStore = $dataStore;
        $this->parseRequestParams($request);
        $this->validate();
    }

    /**
     * Get any validation errors found during parsing and validation.
     *
     * @return array  List of validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Parse the request parameters and store them in $data.
     *
     * @param RequestInterface $request
     */
    protected function parseRequestParams(RequestInterface $request): void
    {
        $params = $request->getQueryParams();
        $this->createFromArray($params);

        // update redirect url with valid value
        $this->set('redirect_url', $this->getValidRedirectUrl());

        // update scope with valid value
        $this->set('scope', $this->getValidScope());
    }

    /**
     * Get redirect URL. It must match one of the registered redirect URLs.
     * If it is missing, but there is only one in the registered URLs, return that one.
     */
    protected function getValidRedirectUrl(): ?string
    {
        $url = $this->get('redirect_url');
        $clientId = $this->get('client_id');

        if (!$clientId) {
            return null;
        }

        $definedUrls = $this->dataStore->getRedirectUrls($clientId);

        // if there is only one URL, return it
        if (count($definedUrls) === 1 && !$url) {
            return $definedUrls[0];
        }

        if ($url && in_array($url, $definedUrls)) {
            return $url;
        }

        return null;
    }

    /**
     * Calculate the valid scope. It must be an intersection of requested and available scopes.
     */
    protected function getValidScope(): ?string
    {
        $scope = $this->get('scope');
        $availableScopes = $this->dataStore->getScopes();

        if (!$scope || !$availableScopes) {
            return null;
        }

        $scope = array_intersect(explode(' ', $scope), $availableScopes);
        return implode(' ', $scope);
    }

    /**
     * Validate the request parameters. Populates $validationErrors with any errors found.
     */
    protected function validate(): void
    {
        $this->validationErrors = [];

        // check required params
        $requiredParams = array_diff($this->fields, 'state');
        foreach ($requiredParams as $param) {
            if ($this->get($param) === null) {
                $this->validationErrors[] = "Missing or invalid required parameter: $param";
            }
        }

        $response_type = $this->get('response_type');
        $client_id = $this->get('client_id');
        $scope = $this->get('scope');
        $code_challenge_method = $this->get('code_challenge_method');

        // currently, only 'code' response type is supported
        if ($response_type !== AuthorizationCode::RESPONSE_TYPE) {
            $this->validationErrors[] = static::validationMessages['invalid_response_type'];
        }

        if (!$this->dataStore->clientExists($client_id)) {
            $this->validationErrors[] = static::validationMessages['invalid_client_id'];
        }

        if (empty($scope)) {
            $this->validationErrors[] = static::validationMessages['invalid_scope'];
        }

        // code challenge method must be 'S256' (SHA-256)
        if ($code_challenge_method !== 'S256') {
            $this->validationErrors[] = static::validationMessages['invalid_code_challenge_method'];
        }
    }
}
