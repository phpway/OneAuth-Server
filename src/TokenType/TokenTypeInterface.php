<?php

namespace OneAuth\TokenType;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;

interface TokenTypeInterface
{
    public function getAccessToken(RequestInterface $request): ?string;
}
