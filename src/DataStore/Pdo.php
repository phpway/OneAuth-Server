<?php
namespace OneAuth\DataStore;

class Pdo implements DataStoreInterface
{
    protected $db;
    protected $tables;

    const PREFIX_TABLE = 'oneauth_';

    const TABLE_CLIENTS = 'clients';
    const TABLE_SCOPES = 'scopes';
    const TABLE_AUTHORIZATION_CODES = 'authorization_codes';
    const TABLE_ACCESS_TOKENS = 'access_tokens';
    const TABLE_USERS = 'users';

    public function __construct($dsn, $username = null, $password = null, $options = array(), array $tables = [])
    {
        $this->db = new \PDO($dsn, $username, $password, $options);

        // determine table names:
        // - use value passed in $tables if it exists
        // - otherwise, use default value (id prefixed by oneauth_)
        $tableIds = [
            static::TABLE_CLIENTS,
            static::TABLE_SCOPES,
            static::TABLE_AUTHORIZATION_CODES,
            static::TABLE_ACCESS_TOKENS,
            static::TABLE_USERS,
        ];
        $this->tables = array_combine(
            $tableIds,
            array_map(function ($id) use ($tables) {
                return array_key_exists($id, $tables) ? $tables[$id]: static::PREFIX_TABLE . $id;
            }, $tableIds)
        );
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

    public function getScopes(): array
    {
        $stmt = $this->db->prepare(sprintf("SELECT scope FROM %s", $this->getTable(static::TABLE_SCOPES)));
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $data;
    }

    public function getRedirectUrls(string $clientId): array
    {
        $urls = [];
        try {
            $clientData = $this->getClientData($clientId);
            $urls = preg_split('/\s+/', $clientData['redirect_url']);
        } catch (\Exception $e) {
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
                $this->getTable(static::TABLE_AUTHORIZATION_CODES)
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
                $this->getTable(static::TABLE_ACCESS_TOKENS)
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
            sprintf("SELECT * FROM %s WHERE authorization_code = :authorization_code LIMIT 1", $this->getTable(static::TABLE_AUTHORIZATION_CODES)),
            ['authorization_code' => $authorization_code]
        );

        return is_array($result) ? $result : array('error' => 'Authorization code not found');
    }

    public function deleteAuthorizationCode(string $code): void
    {
        $stmt = $this->db->prepare(
            sprintf("DELETE FROM %s WHERE authorization_code = :authorization_code", $this->getTable(static::TABLE_AUTHORIZATION_CODES))
        );

        $result = $stmt->execute(['authorization_code' => $code]);
        if (!$result) {
            throw new Exception("Failed to delete authorization code");
        }
    }

    public function getUserByUsername(string $username): ?array
    {
        $result = $this->fetch(
            sprintf("SELECT * FROM %s WHERE username = :username LIMIT 1", $this->getTable(static::TABLE_USERS)),
            ['username' => $username]
        );

        return $result ?: null;
    }

    public function createUser(array $user): string
    {
        $stmt = $this->db->prepare(
            sprintf(<<<SQL
                INSERT INTO %s
                    (username, password, first_name, last_name, email, authorizer)
                  VALUES
                    (:username, :password, :first_name, :last_name, :email, :authorizer);
                SQL,
                $this->getTable(static::TABLE_USERS)
            )
        );

        $result = $stmt->execute($user);
        if (!$result) {
            throw new Exception("Failed to save user");
        }

        return $this->db->lastInsertId();
    }

    public function getAccessToken(string $token): ?array
    {
        $result = $this->fetch(
            sprintf("SELECT * FROM %s WHERE access_token = :access_token LIMIT 1", $this->getTable(static::TABLE_ACCESS_TOKENS)),
            ['access_token' => $token]
        );

        return $result ?: null;
    }

    public function deleteAccessToken(string $token): void
    {
        $stmt = $this->db->prepare(
            sprintf("DELETE FROM %s WHERE access_token = :access_token", $this->getTable(static::TABLE_ACCESS_TOKENS))
        );

        $result = $stmt->execute(['access_token' => $token]);
        if (!$result) {
            throw new Exception("Failed to delete access token");
        }
    }

    public function deleteAllAccessTokensForUser(string $clientId, string $userId): void
    {
        $stmt = $this->db->prepare(
            sprintf("DELETE FROM %s WHERE client_id = :client_id AND user_id = :user_id", $this->getTable(static::TABLE_ACCESS_TOKENS))
        );

        $result = $stmt->execute(['client_id' => $clientId, 'user_id' => $userId]);
        if (!$result) {
            throw new Exception("Failed to delete access tokens for user");
        }
    }

    protected function getClientData(string $clientId)
    {
        $clientData = $this->fetch(
            sprintf("SELECT * FROM %s WHERE client_id = :client_id", $this->getTable(static::TABLE_CLIENTS)),
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

    protected function getTable($name)
    {
        return array_key_exists($name, $this->tables) ? $this->tables[$name] : null;
    }
}
