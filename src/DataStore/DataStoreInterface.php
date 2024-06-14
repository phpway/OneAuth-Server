<?php
namespace OneAuth\DataStore;

interface DataStoreInterface
{
    public function clientExists(string $clientId): bool;
    public function getScopes(): array;
    public function getRedirectUrls(string $clientId): array;
    public function saveAuthorizationCode(AuthorizationCode $authorizationCode): void;
    public function saveAccessToken(AccessToken $token): void;
    public function getAuthorizationCode($authorization_code): array;
    public function deleteAuthorizationCode(string $code): void;
    public function getUserByUsername(string $username): ?array;
    public function getAccessToken(string $token): ?array;
    public function deleteAccessToken(string $token): void;
    public function deleteAllAccessTokensForUser(string $clientId, string $userId): void;
}
