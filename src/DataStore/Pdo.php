<?php
namespace OneAuth\DataStore;

class Pdo implements DataStoreInterface
{
    protected $db;

    const tables = [
        'clients' => 'oauth_clients',
        'scopes' => 'oauth_scopes',
        'authorization_codes' => 'oauth_authorization_codes',
        'access_tokens' => 'oauth_access_tokens',
    ];

    public function __construct($dsn, $username = null, $password = null, $options = array())
    {
        $this->db = new \PDO($dsn, $username, $password, $options);
    }

    public function getDb()
    {
        return $this->db;
    }

    public function clientExists(string $clientId): bool
    {
        try {
            $this->getClientData($clientId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function scopeExists(string $scope): bool
    {
        $stmt = $this->db->prepare(
            sprintf("SELECT scope FROM %s WHERE scope = :scope", static::tables['scopes'])
        );
        $stmt->execute(['scope' => $scope]);
        $data = $stmt->fetch(\PDO::FETCH_COLUMN);

        return !empty($data);
    }

    public function getRedirectUrls(string $clientId): array
    {
        $clientData = $this->getClientData($clientId);
        $urls = preg_split('/\s+/', $clientData['redirect_url']);

        if (empty($urls)) {
            throw new Exception("Client ID not found");
        }

        return $urls;
    }

    public function saveAuthorizationCode(AuthorizationCode $code): void
    {
        $stmt = $this->db->prepare(
            sprintf(<<<SQL
                INSERT INTO %s
                    (authorization_code, client_id, redirect_url, scope, state, code_challenge, code_challenge_method, expires, user_id)
                  VALUES
                    (:authorization_code, :client_id, :redirect_url, :scope, :state, :code_challenge, :code_challenge_method, :expires, :user_id);
                SQL,
                static::tables['authorization_codes']
            )
        );

        $result = $stmt->execute($code->getData());
        if (!$result) {
            throw new Exception("Failed to save authorization code");
        }
    }

    public function saveAccessToken(AccessToken $token): void
    {
        $stmt = $this->db->prepare(
            sprintf(<<<SQL
                INSERT INTO %s
                    (access_token, client_id, scope, expires, user_id)
                  VALUES
                    (:access_token, :client_id, :scope, :expires, :user_id);
                SQL,
                static::tables['access_tokens']
            )
        );

        $result = $stmt->execute($token->getData());
        if (!$result) {
            throw new Exception("Failed to save access token");
        }
    }

    public function getAuthorizationCode($authorization_code): array
    {
        $result = $this->fetch(
            sprintf("SELECT * FROM %s WHERE authorization_code = :authorization_code LIMIT 1", static::tables['authorization_codes']),
            ['authorization_code' => $authorization_code]
        );

        return is_array($result) ? $result : array('error' => 'Authorization code not found');
    }

    public function deleteAuthorizationCode(string $code): void
    {
        $stmt = $this->db->prepare(
            sprintf("DELETE FROM %s WHERE authorization_code = :authorization_code", static::tables['authorization_codes'])
        );

        $result = $stmt->execute(['authorization_code' => $code]);
        if (!$result) {
            throw new Exception("Failed to delete authorization code");
        }
    }

    protected function getClientData(string $clientId)
    {
        $clientData = $this->fetch(
            sprintf("SELECT * FROM %s WHERE client_id = :client_id", static::tables['clients']),
            ['client_id' => $clientId]
        );

        if (empty($clientData)) {
            throw new Exception("Client ID not found");
        }

        return $clientData;
    }

    protected function fetch($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
