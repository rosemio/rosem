<?php

namespace Rosem\Component\Authentication\Provider;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Rosem\Component\Authentication\Middleware\{
    BasicAuthenticationMiddleware,
    DigestAuthenticationMiddleware
};
use Rosem\Contract\Authentication\UserFactoryInterface;
use Rosem\Contract\Container\ServiceProviderInterface;
use Rosem\Contract\Http\Server\MiddlewareCollectorInterface;

class HttpAuthenticationProvider implements ServiceProviderInterface
{
    public const CONFIG_TYPE = 'auth.http.type';

    public const CONFIG_USER_PASSWORD_RESOLVER = 'auth.http.user.passwordResolver';

    public const CONFIG_USER_LIST = 'auth.http.user.list';

    public const CONFIG_REALM = 'auth.http.realm';

    /**
     * @inheritdoc
     */
    public function getFactories(): array
    {
        return [
            //@TODO constants
            static::CONFIG_REALM => static fn(ContainerInterface $container) =>
                $container->has('AUTH_HTTP_REALM')
                    ? $container->get('AUTH_HTTP_REALM')
                    : $container->get('APP_NAME'),
            static::CONFIG_TYPE => static fn(): string => 'digest',
            static::CONFIG_USER_PASSWORD_RESOLVER => static function (ContainerInterface $container): callable {
                return static function (string $username) use (&$container): ?string {
                    return $container->get(static::CONFIG_USER_LIST)[$username] ?? null;
                };
            },
            BasicAuthenticationMiddleware::class => static fn(ContainerInterface $container
            ): BasicAuthenticationMiddleware => new BasicAuthenticationMiddleware(
                $container->get(ResponseFactoryInterface::class),
                $container->get(UserFactoryInterface::class),
                $container->get(static::CONFIG_USER_PASSWORD_RESOLVER),
                $container->get(static::CONFIG_REALM)
            ),
            DigestAuthenticationMiddleware::class => static fn(ContainerInterface $container
            ): DigestAuthenticationMiddleware => new DigestAuthenticationMiddleware(
                $container->get(ResponseFactoryInterface::class),
                $container->get(UserFactoryInterface::class),
                $container->get(static::CONFIG_USER_PASSWORD_RESOLVER),
                $container->get(static::CONFIG_REALM)
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getExtensions(): array
    {
        return [
            MiddlewareCollectorInterface::class => static function (
                ContainerInterface $container,
                MiddlewareCollectorInterface $middlewareCollector
            ): void {
                $middlewareCollector->addMiddleware(
                    $container->get(static::CONFIG_TYPE) === 'basic'
                        ? $container->get(BasicAuthenticationMiddleware::class)
                        : $container->get(DigestAuthenticationMiddleware::class)
                );
            },
        ];
    }
}
