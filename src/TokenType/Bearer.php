<?php

namespace OneAuth\TokenType;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;

class Bearer implements TokenTypeInterface
{
    /**
     * Get the access token from the request. Extract it from the Authorization header.
     * Example: 'Authorization: Bearer xxxxxxxx'
     */
    public function getAccessToken(RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
