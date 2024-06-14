<?php

namespace OneAuth\Model;

use OneAuth\DataStore\DataStoreInterface;
use OneAuth\Data\AbstractData;

class AccessToken extends AbstractData
{
    protected $fields = [
        'access_token',
        'client_id',
        'user_id',
        'expires',
        'scope',
    ];

    protected $dataStore;

    /**
     * Enforce creating instance by one the static factory methods to ensure the token exists in DataStore.
     */
    protected function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    public static function createFromTokenValue(string $token, DataStoreInterface $dataStore): ?AccessToken
    {
        $tokenData = $dataStore->getAccessToken($token);
        if (!$tokenData) {
            return null;
        }

        $instance = new static($dataStore);
        $instance->createFromArray($tokenData);

        // convert timestamp fields to unix timestamps
        $instance->convertTimestampFields();

        return $instance;
    }

    protected function convertTimestampFields(): void
    {
        $this->set('created', strtotime($this->get('created')));
        $this->set('expires', strtotime($this->get('expires')));
    }

    public function checkScope(string $scope): bool
    {
        return strpos($this->get('scope'), $scope) !== false;
    }

    public function isValid(): bool
    {
        return (int) $this->get('expires') > time();
    }

    public function delete(): void
    {
        $this->dataStore->deleteAccessToken($this->get('access_token'));
        $this->destroy();
    }

    /**
     * Delete all access tokens for the user associated with this token (for same client only).
     */
    public function deleteAllForUser(): void
    {
        $this->dataStore->deleteAllAccessTokensForUser($this->get('client_id'), $this->get('user_id'));
        $this->destroy();
    }

    protected function destroy(): void
    {
        foreach ($this->fields as $field) {
            $this->set($field, null);
        }
    }
}
