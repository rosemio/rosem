<?php

namespace Rosem\Authentication\Http\Server;

use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};
use Psr\Http\Message\ResponseFactoryInterface;
use Rosem\Authentication\User;
use Rosem\Psr\Authentication\UserInterface;
use function call_user_func;
use function count;
use function strlen;

/** @noinspection LongInheritanceChainInspection */
class DigestAuthenticationMiddleware extends BasicAuthenticationMiddleware
{
    /**
     * Authorization header prefix.
     */
    private const AUTHORIZATION_HEADER_PREFIX = 'Digest';

    /**
     * Authorization header needed parts.
     */
    private const AUTHORIZATION_HEADER_NEEDED_PARTS = [
        'username', 'nonce', 'uri', 'response', 'qop', 'nc', 'cnonce',
    ];

    /**
     * @var string|null The nonce value
     */
    protected $nonce;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        callable $userPasswordResolver,
        ?callable $userRolesResolver = null,
        ?callable $userDetailsResolver = null,
        string $realm = 'Login',
        string $nonce = ''
    ) {
        parent::__construct($responseFactory, $userPasswordResolver, $userRolesResolver, $userDetailsResolver, $realm);

        $this->nonce = $nonce ?: uniqid('', true);
    }

    /**
     * Check the user credentials and return the username or false.
     *
     * @param ServerRequestInterface $request
     *
     * @return UserInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        $authHeader = $request->getHeader('Authorization');

        /** @noinspection NotOptimalIfConditionsInspection */
        if (empty($authHeader)
            || strpos(reset($authHeader), self::AUTHORIZATION_HEADER_PREFIX . ' ') !== 0
            || !preg_match_all(
                '/('
                . implode('|', self::AUTHORIZATION_HEADER_NEEDED_PARTS)
                . ')=(?|\'([^\']+?)\'|"([^"]+?)"|([^\s,]+))/',
                substr(reset($authHeader), strlen(self::AUTHORIZATION_HEADER_PREFIX) + 1),
                $matches,
                PREG_SET_ORDER
            )
            || count($matches) !== count(self::AUTHORIZATION_HEADER_NEEDED_PARTS)
        ) {
            return null;
        }

        $authorization = [];

        /** @var array[] $matches */
        foreach ($matches as $match) {
            $authorization[$match[1]] = $match[2];
        }

        $identity = $authorization['username'];
        $password = call_user_func($this->userPasswordResolver, $identity, $request);

        if (!$password
            || $authorization['response'] !== md5(sprintf(
                '%s:%s:%s:%s:%s:%s',
                md5("{$authorization['username']}:$this->realm:$password"),
                $authorization['nonce'],
                $authorization['nc'],
                $authorization['cnonce'],
                $authorization['qop'],
                md5($request->getMethod() . ':' . $authorization['uri'])
            ))
        ) {
            return null;
        }

        return new User(
            $identity,
            call_user_func($this->userRolesResolver, $identity),
            call_user_func($this->userDetailsResolver, $identity)
        );
    }

    /**
     * Create unauthorized response.
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createUnauthorizedResponse(): ResponseInterface
    {
        $realm = $this->realm;

        return $this->responseFactory->createResponse(401)
            ->withHeader(
                'WWW-Authenticate',
                sprintf(
                    self::AUTHORIZATION_HEADER_PREFIX . ' realm="%s",qop="auth",nonce="%s",opaque="%s"',
                    $realm,
                    $this->nonce,
                    md5($realm)
                )
            );
    }
}
