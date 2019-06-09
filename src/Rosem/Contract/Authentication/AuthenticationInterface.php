<?php

namespace Rosem\Contract\Authentication;

use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};

interface AuthenticationInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return UserInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?UserInterface;

    /**
     * Create unauthorized response.
     *
     * @return ResponseInterface
     */
    public function createUnauthorizedResponse(): ResponseInterface;
}
