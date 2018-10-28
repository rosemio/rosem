<?php

namespace Rosem\Authentication;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use Rosem\Authentication\Http\Server\AuthenticationMiddleware;
use Rosem\Psr\Authentication\UserFactoryInterface;
use Rosem\Psr\Container\ServiceProviderInterface;
use Rosem\Psr\Http\Server\MiddlewareDispatcherInterface;
use Rosem\Psr\Template\TemplateRendererInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
{
    public const CONFIG_SYMMETRIC_KEY = 'auth.symmetricKey';

    public const CONFIG_USER_RESOLVER_PASSWORD = 'auth.user.resolver.password';

    public const CONFIG_PARAMETER_IDENTITY = 'auth.parameter.identity';

    public const CONFIG_PARAMETER_PASSWORD = 'auth.parameter.password';

    public const CONFIG_URI_LOGIN = 'auth.uri.login';

    public const CONFIG_URI_LOGGED_IN = 'auth.uri.loggedIn';

    /**
     * {@inheritdoc}
     */
    public function getFactories(): array
    {
        return [
            static::CONFIG_SYMMETRIC_KEY => function () {
                return 'mBC5v1sOKVvbdEitdSBenu59nfNfhwkedkJVNabosTw=';
            },
            static::CONFIG_USER_RESOLVER_PASSWORD => function () {
                return function (string $username): ?string {
                    return ['admin' => 'admin'][$username] ?? null;
                };
            },
            static::CONFIG_PARAMETER_IDENTITY => function () {
                return 'username';
            },
            static::CONFIG_PARAMETER_PASSWORD => function () {
                return 'password';
            },
            static::CONFIG_URI_LOGIN => function () {
                return '/login';
            },
            static::CONFIG_URI_LOGGED_IN => function () {
                return '/';
            },
            SessionMiddleware::class => function (ContainerInterface $container) {
//                return SessionMiddleware::fromSymmetricKeyDefaults(
//                    $container->get(static::CONFIG_SYMMETRIC_KEY),
//                    20 * 60 // 20 minutes
//                );

                $symmetricKey = $container->get(static::CONFIG_SYMMETRIC_KEY);

                return new SessionMiddleware(
                    new \Lcobucci\JWT\Signer\Hmac\Sha256(),
                    $symmetricKey,
                    $symmetricKey,
                    \Dflydev\FigCookies\SetCookie::create('session')
                        ->withSecure(PHP_SAPI !== 'cli-server')
                        ->withHttpOnly(true)
                        ->withPath('/'),
                    new \Lcobucci\JWT\Parser(),
                    20 * 60, // 20 minutes,
                    new \Lcobucci\Clock\SystemClock()
                );
            },
            AuthenticationMiddleware::class => [static::class, 'createAuthenticationMiddleware'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return [
            TemplateRendererInterface::class => function (
                ContainerInterface $container,
                TemplateRendererInterface $renderer
            ) {
                $renderer->addGlobalData([
                    'csrfToken' => '4sWPhTlJAmt1IcyNq1FCyivsAVhHqjiDCKRXOgOQock=',
                ]);
            },
            MiddlewareDispatcherInterface::class => function (
                ContainerInterface $container,
                MiddlewareDispatcherInterface $dispatcher
            ) {
                $dispatcher->use(SessionMiddleware::class);
            },
        ];
    }

    public function createAuthenticationMiddleware(ContainerInterface $container): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(UserFactoryInterface::class),
            $container->get(static::CONFIG_USER_RESOLVER_PASSWORD),
            $container->get(static::CONFIG_PARAMETER_IDENTITY),
            $container->get(static::CONFIG_PARAMETER_PASSWORD),
            $container->get(static::CONFIG_URI_LOGIN),
            $container->get(static::CONFIG_URI_LOGGED_IN)
        );
    }
}
