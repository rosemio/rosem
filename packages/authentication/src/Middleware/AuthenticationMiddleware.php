<?php

declare(strict_types=1);

namespace Rosem\Component\Authentication\Middleware;

use Fig\Http\Message\{
    RequestMethodInterface as RequestMethod,
    StatusCodeInterface as StatusCode
};
use InvalidArgumentException;
use Psr\Http\Message\{
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestInterface
};
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use Rosem\Component\Authentication\Exception\AuthenticationException;
use Rosem\Contract\Authentication\{
    UserFactoryInterface,
    UserInterface
};

use Rosem\Contract\Hash\HasherInterface;
use function call_user_func;

class AuthenticationMiddleware extends AbstractAuthenticationMiddleware
{
    /**
     * Authorization header prefix.
     */
    public const AUTHORIZATION_HEADER_PREFIX = 'Bearer';

    protected HasherInterface $hasher;

    protected string $identityParameter;

    protected string $passwordParameter;

    protected string $loginUri;

    protected string $loggedInUri;

    /**
     * AuthenticationMiddleware constructor.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        UserFactoryInterface $userFactory,
        HasherInterface $hasher,
        callable $userPasswordResolver,
        string $identityParameter = 'username',
        string $passwordParameter = 'password',
        ?string $loginUri = '/login',
        ?string $loggedInUri = '/'
    ) {
        parent::__construct($responseFactory, $userFactory, $userPasswordResolver);

        $this->hasher = $hasher;
        $this->identityParameter = $identityParameter;
        $this->passwordParameter = $passwordParameter;
        $this->loginUri = $loginUri;
        $this->loggedInUri = $loggedInUri;
    }

    /**
     * @return AuthenticationMiddleware
     */
    public function withLoginUri(string $uri): self
    {
        $new = clone $this;
        $new->setLoginUri($uri);

        return $new;
    }

    /**
     * @return AuthenticationMiddleware
     */
    public function withLoggedInUri(string $uri): self
    {
        $new = clone $this;
        $new->setLoggedInUri($uri);

        return $new;
    }

    public function withIdentityParameter(string $identityParameter): self
    {
        $new = clone $this;
        $new->setIdentityParameter($identityParameter);

        return $new;
    }

    public function withPasswordParameter(string $passwordParameter): self
    {
        $new = clone $this;
        $new->setPasswordParameter($passwordParameter);

        return $new;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler
    ): ResponseInterface {
        $user = $this->authenticate($request);

        if ($user) {
            $response = $requestHandler->handle($request->withAttribute(UserInterface::class, $user));

            if ($this->loggedInUri && rtrim($request->getUri()->getPath(), '/') !== $this->loggedInUri) {
                return $response->withStatus(StatusCode::STATUS_FOUND)
                    ->withHeader('Location', $this->loggedInUri);
            }

            return $response;
        }

        if ($this->loginUri && $request->getMethod() === RequestMethod::METHOD_GET &&
            rtrim($request->getUri()->getPath(), '/') === $this->loginUri
        ) {
            return $requestHandler->handle($request);
        }

        return $this->createUnauthorizedResponse();
    }

    public function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        if (PHP_SAPI !== 'cli-server' && $request->getUri()->getScheme() !== 'https') {
            throw AuthenticationException::dueToWebServerInsecureHttpConnection();
        }

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $identity = $session->get('identity');

        if (! $identity) {
            if ($request->getMethod() !== RequestMethod::METHOD_POST) {
                return null;
            }

            $body = $request->getParsedBody();

            if (empty($body[$this->identityParameter]) || empty($body[$this->passwordParameter])) {
                return null;
            }

            $identity = $body[$this->identityParameter];
            $password = call_user_func($this->userPasswordResolver, $identity);

            if (! $password || ! $this->hasher->verify($body[$this->passwordParameter], $password)) {
                return null;
            }

            $session->set('identity', $identity);
        }

        return $this->userFactory->createUser($identity);
    }

    /**
     * Create unauthorized response.
     *
     * @throws InvalidArgumentException
     */
    public function createUnauthorizedResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(StatusCode::STATUS_FOUND);

        if ($this->loginUri !== null) {
            return $response->withHeader('Location', $this->loginUri);
        }

        return $response;
    }

    private function setLoginUri(string $uri): void
    {
        $this->loginUri = $uri;
    }

    private function setLoggedInUri(string $uri): void
    {
        $this->loggedInUri = $uri;
    }

    private function setIdentityParameter(string $identityParameter): void
    {
        $this->identityParameter = $identityParameter;
    }

    private function setPasswordParameter(string $passwordParameter): void
    {
        $this->passwordParameter = $passwordParameter;
    }
}
