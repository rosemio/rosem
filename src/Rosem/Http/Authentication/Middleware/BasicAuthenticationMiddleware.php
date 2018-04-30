<?php

namespace Rosem\Http\Authentication\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rosem\Http\Authentication\AbstractHttpAuthentication;
use Rosem\Http\Factory\ResponseFactory;

class BasicAuthenticationMiddleware extends AbstractHttpAuthentication implements MiddlewareInterface
{
    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $username = $this->login($request);

        if ($username === false) {
            return (new ResponseFactory)->createResponse(401) //TODO
                ->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
        }

        if ($this->attribute !== null) {
            $request = $request->withAttribute($this->attribute, $username);
        }

        return $handler->handle($request);
    }

    /**
     * Check the user credentials and return the username or false.
     * @return false|string
     */
    private function login(ServerRequestInterface $request)
    {
        //Check header
        $authorization = $this->parseHeader($request->getHeaderLine('Authorization'));

        if (!$authorization) {
            return false;
        }

        //Check the user
        if (!isset($this->users[$authorization['username']])) {
            return false;
        }

        if ($this->users[$authorization['username']] !== $authorization['password']) {
            return false;
        }

        return $authorization['username'];
    }

    /**
     * Parses the authorization header for a basic authentication.
     * @return false|array
     */
    private function parseHeader(string $header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return false;
        }
        $header = explode(':', base64_decode(substr($header, 6)), 2);

        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }
}
