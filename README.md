# OneAuth Server

OneAuth server is very simple OAuth2 server implementation with limited functionality specifically designed for authenticating single page applications (SPA) according to the workflow described below with exclusive use of reference tokens only.

If you need a fully-featured OAuth2 server implementation, you can look up the official OAuth page [https://oauth.net/code/php/](https://oauth.net/code/php/).

## Features

At the moment, OneAuth server provides 2 controllers:
 - **`AuthorizeController`** - handle requests for obtaining the `Authorization Code` which is one-time use, short-lived random code to be used by SPA for obtaining the `Access Token`.
 - **`TokenController`** - handle requests for obtaining the `Access Token`.

Request data required for each controller together with response details are described in the workflow below.

Since OneAuth server designed for authenticating SPAs, all communication between OneAuth server and the client app is transparent to the user (either via url search params, or via sending form data in POST requests), meaning there is no back channel for exchanging data between these two entities.

Thus, there is no `client secret` in the workflow as the SPA has no mechanism to hide it. But it uses PKCE protection that requires the client app sending `code verifier` (random string) hash in the initial request for the authorization code, and then sending code verifier in plain text when exchanging this authorization code for the access token.

## PSR-7 Request / Response

OneAuth server uses [PSR-7](https://www.php-fig.org/psr/psr-7/) compatible request and response objects for handling requests to obtain authorization codes and/or access tokens. Thus, you will need to provide a PSR-7 implementation that best first your application. Few options you can choose from:
 - [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - Slim Framework PSR-7 implementation
 - [httpsoft/http-message](https://github.com/httpsoft/http-message) & [httpsoft/http-server-request](https://github.com/httpsoft/http-server-request) - Fast, strict and lightweight implementation

 To send the response to the client, you will also need to implement a response emitter, or use an existing one (for example [ResponseEmitter.php](https://github.com/slimphp/Slim/blob/4.x/Slim/ResponseEmitter.php) from the Slim framework).

## SPA Workflow

![SPA workflow diagram](/assets/OneAuth_workflow.png)

## TODOs
 - [ ] Add controller for protecting APIs
   - [ ] Recognize token in headers
   - [ ] Recognize token in GET
   - [ ] Recognize token in POST
 - [ ] Add endpoint to return user profile
 - [x] Implement revoking tokens
   - [x] Individual token
   - [x] All for given user
 - [ ] Add functionality for refresh tokens