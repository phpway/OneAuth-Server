# OAuth2 for SPA

This project is very simple OAuth2 server with limited functionality specifically designed for authenticating single page applications (SPA) according to the workflow described below.

Namely, this is not a fully-featured OAuth2 implementation, like for example [oauth2-server-php](https://github.com/bshaffer/oauth2-server-php).

## PSR-7 Request / Response

Server uses PSR-7 compatible request and response objects for handling requests for getting authorization codes and/or access tokens. Thus, you will need to pick a PSR-7 implementation that best first your application. Few choices:
 - [Slim-Psr7](https://github.com/slimphp/Slim-Psr7) - Slim Framework PSR-7 implementation
 - [httpsoft/http-message](https://github.com/httpsoft/http-message) & [httpsoft/http-server-request](https://github.com/httpsoft/http-server-request) - Fast, strict and lightweight implementation

 To send the response, you will also need to implement a response emitter, or use an existing one (for example [ResponseEmitter.php](https://github.com/slimphp/Slim/blob/4.x/Slim/ResponseEmitter.php) from Slim framework).


## SPA Workflow



## TODOs
 - [ ] Add controller for protecting APIs
 - [ ] Add functionality for refresh tokens