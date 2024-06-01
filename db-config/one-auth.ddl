-- DDL to create MySQL OneAuth database and tables for PDO storage

-- Drop any old backups and create a new backup of tables to be created
DROP DATABASE IF EXISTS one_auth_backup;
CREATE DATABASE one_auth_backup;
USE one_auth_backup;

-- Temporarily create one_auth database with blank tables if it doesn't exist, otherwise the next block would fail
CREATE DATABASE IF NOT EXISTS one_auth;
USE one_auth;

CREATE TABLE IF NOT EXISTS oauth_access_tokens (id VARCHAR(1));
CREATE TABLE IF NOT EXISTS oauth_authorization_codes (id VARCHAR(1));
CREATE TABLE IF NOT EXISTS oauth_clients (id VARCHAR(1));
CREATE TABLE IF NOT EXISTS oauth_scopes (id VARCHAR(1));
CREATE TABLE IF NOT EXISTS oauth_users (id VARCHAR(1));

-- Create copies of all production tables
USE one_auth_backup;
CREATE TABLE oauth_access_tokens AS SELECT * FROM one_auth.oauth_access_tokens;
CREATE TABLE oauth_authorization_codes AS SELECT * FROM one_auth.oauth_authorization_codes;
CREATE TABLE oauth_clients AS SELECT * FROM one_auth.oauth_clients;
CREATE TABLE oauth_scopes AS SELECT * FROM one_auth.oauth_scopes;
CREATE TABLE oauth_users AS SELECT * FROM one_auth.oauth_users;

-- Create one_auth database and tables
DROP DATABASE IF EXISTS one_auth;
CREATE DATABASE one_auth;
USE one_auth;

CREATE TABLE oauth_access_tokens (
  access_token         VARCHAR(40)    NOT NULL COMMENT 'System generated access token. Use appropriate COLLATION for case-sensitive tokens',
  client_id            VARCHAR(80)             COMMENT 'OAUTH_CLIENTS.CLIENT_ID',
  user_id              VARCHAR(80)             COMMENT 'OAUTH_USERS.USER_ID',
  expires              TIMESTAMP      NOT NULL COMMENT 'When the token becomes invalid',
  scope                VARCHAR(4000)           COMMENT 'Space-delimited list of scopes token can access',
  PRIMARY KEY (access_token)
);

CREATE TABLE oauth_authorization_codes (
  authorization_code      VARCHAR(40)    NOT NULL COMMENT 'System generated authorization code',
  client_id               VARCHAR(80)             COMMENT 'OAUTH_CLIENTS.CLIENT_ID',
  user_id                 VARCHAR(80)             COMMENT 'OAUTH_USERS.USER_ID',
  redirect_uri            VARCHAR(2000)  NOT NULL COMMENT 'URI to redirect user after authorization',
  expires                 TIMESTAMP      NOT NULL COMMENT 'When the code becomes invalid',
  scope                   VARCHAR(4000)           COMMENT 'Space-delimited list scopes code can request',
  state                   VARCHAR(128)            COMMENT 'State for the authorization code',
  code_challenge          VARCHAR(128)   NOT NULL COMMENT 'Code challenge hash for PKCE',
  code_challenge_method   VARCHAR(8)     NOT NULL COMMENT 'Code challenge hash method for PKCE',
  PRIMARY KEY (authorization_code)
);

CREATE TABLE oauth_clients (
  client_id            VARCHAR(80)   NOT NULL COMMENT 'A unique client identifier',
  redirect_url         VARCHAR(2000) NOT NULL COMMENT 'Redirect URL used for Authorization Grant',
  scope                VARCHAR(4000)          COMMENT 'Space-delimited list of permitted scopes',
  user_id              VARCHAR(80)            COMMENT 'OAUTH_USERS.USER_ID',
  PRIMARY KEY (client_id)
);

CREATE TABLE oauth_scopes (
  scope                VARCHAR(80)    NOT NULL COMMENT 'Name of scope, without spaces',
  is_default           BOOLEAN                 COMMENT 'True to grant scope',
  PRIMARY KEY (scope)
);

CREATE TABLE oauth_users (
  username            VARCHAR(80),
  password            VARCHAR(255),
  first_name          VARCHAR(80),
  last_name           VARCHAR(80),
  email               VARCHAR(80),
  scope               VARCHAR(4000),
  PRIMARY KEY (username)
);

SHOW TABLES;