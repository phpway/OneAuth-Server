<?php

namespace OneAuth\Request;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Data\AbstractData;
use OneAuth\ResponseType\AccessToken;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class TokenParams extends AbstractData
{
    const validationMessages = [
        'invalid_method' => 'Invalid request method. Must be POST',
        'invalid_grant_type' => 'Invalid grant type',
        'missing_code' => 'Missing code',
        'missing_code_verifier' => 'Missing code verifier',
        'invalid_code_challenge' => 'Invalid code challenge',
        'invalid_code' => 'Invalid code',
        'mismatch_client_id' => 'Client ID mismatch',
        'mismatch_redirect_url' => 'Redirect URL mismatch',
        'expired_code' => 'Authorization code has expired',
    ];

    protected $dataStore;

    protected $fields = [
        'grant_type',
        'code',
        'redirect_url',
        'client_id',
        'code_verifier',
    ];

    protected $validationErrors = [];
    protected $authorizatonCode = null;

    /**
     * Create a new TokenParams instance. It will automatically parse and validate the given request.
     *
     * @param DataStoreInterface  $dataStore    Data store to check client ID, scope, and redirect URL
     * @param RequestInterface    $request      Authorization request object
     */
    public function __construct(DataStoreInterface $dataStore, RequestInterface $request)
    {
        $this->dataStore = $dataStore;
        $this->parseRequestData($request);
        $this->validate($request);
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
     * Get the user ID associated with the authorization code.
     */
    public function getAuthorizationCode(): ?array
    {
        return $this->authorizatonCode;
    }

    /**
     * Parse the request data and store them in $data.
     *
     * @param RequestInterface $request
     */
    protected function parseRequestData(RequestInterface $request): void
    {
        $data = $request->getParsedBody();
        $this->createFromArray($data);
    }

    /**
     * Validate the request parameters. Populates $validationErrors with any errors found.
     * Any invalid parameters will be cleared from $data.
     */
    protected function validate(RequestInterface $request): void
    {
        $this->validationErrors = [];

        // request must be POST
        if ($request->getMethod() !== 'POST') {
            $this->validationErrors[] = static::validationMessages['invalid_method'];
        }

        $grantType = $this->get('grant_type');
        $code = $this->get('code');
        $codeVerifier = $this->get('code_verifier');

        // grant type must be 'authorization_code'
        if ($grantType !== AccessToken::GRANT_TYPE) {
            $this->validationErrors[] = static::validationMessages['invalid_grant_type'];
        }

        if (!$code) {
            $this->validationErrors[] = static::validationMessages['missing_code'];
        }

        if (!$codeVerifier) {
            $this->validationErrors[] = static::validationMessages['missing_code_verifier'];
        }

        // check against previously saved authorization code token
        $token = $this->dataStore->getAuthorizationCode($code);
        if (empty($token)) {
            $this->validationErrors[] = static::validationMessages['invalid_code'];
        }

        $clientId = $token['client_id'] ?? null;
        $redirectUrl = $token['redirect_url'] ?? null;

        if ($clientId !== $this->get('client_id')) {
            $this->validationErrors[] = static::validationMessages['mismatch_client_id'];
        }
        if ($redirectUrl !== $this->get('redirect_url')) {
            $this->validationErrors[] = static::validationMessages['mismatch_redirect_url'];
        }

        // remember authorization token
        $this->authorizatonCode = $token;

        // verify code challenge
        $codeChallenge = $token['code_challenge'] ?? null;
        $codeChallengeMethod = $token['code_challenge_method'] ?? null;
        $hash = null;
        switch ($codeChallengeMethod) {
            case 'S256':
                $hash = base64_encode(hash('sha256', $codeVerifier));
                break;
        }
        if (!$hash || $hash !== $codeChallenge) {
            $this->validationErrors[] = static::validationMessages['invalid_code_challenge'];
        }

        // check if token is expired
        $expires = $token['expires'] ?? null;
        if (null === $expires || strtotime($expires) < time()) {
            $this->validationErrors[] = static::validationMessages['expired_code'];
        }
    }
}
