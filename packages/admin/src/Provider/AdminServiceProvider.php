<?php

namespace Rosem\Component\Admin\Provider;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Rosem\Component\Admin\Http\Server\{
    AdminRequestHandler,
    LoginRequestHandler
};
use Rosem\Component\Authentication\Middleware\AuthenticationMiddleware;
use Rosem\Component\Http\Server\RequestHandler;
use Rosem\Contract\Container\ServiceProviderInterface;
use Rosem\Contract\Route\HttpRouteCollectorInterface;
use Rosem\Contract\Template\TemplateRendererInterface;

use function dirname;

class AdminServiceProvider implements ServiceProviderInterface
{
    public const CONFIG_USER_IDENTITY = 'admin.user.identity';

    public const CONFIG_USER_PASSWORD = 'admin.user.password';

    public const CONFIG_USER_RESOLVER_PASSWORD = 'admin.user.resolver.password';

    public const CONFIG_URI_LOGGED_IN = 'admin.uri.loggedIn';

    public const CONFIG_URI_LOGIN = 'admin.uri.login';

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * @return callable[]
     */
    public function getFactories(): array
    {
        return [
            static::CONFIG_USER_RESOLVER_PASSWORD => static function (ContainerInterface $container): callable {
                return static function (string $userIdentity) use (&$container): ?string {
                    return [
                               $container->get(static::CONFIG_USER_IDENTITY) =>
                                   $container->get(static::CONFIG_USER_PASSWORD),
                           ][$userIdentity] ?? null;
                };
            },
            'admin.meta.titlePrefix' =>
                static fn(ContainerInterface $container): string => ($container->has('app.name')
                        ? $container->get('app.name') . ' '
                        : ''
                    ) . 'Admin | ',
            'admin.meta.title' => static fn(): string => 'Welcome',
            'admin.meta.titleSuffix' => static fn(): string => '',
            static::CONFIG_URI_LOGGED_IN => static fn(): string => '/admin',
            static::CONFIG_URI_LOGIN => static fn(ContainerInterface $container): string => '/' . trim(
                    $container->get(static::CONFIG_URI_LOGGED_IN),
                    '/'
                ) . '/login',
            AdminRequestHandler::class => static fn(ContainerInterface $container
            ): AdminRequestHandler => new AdminRequestHandler(
                $container->get(ResponseFactoryInterface::class),
                $container->has(TemplateRendererInterface::class)
                    ? $container->get(TemplateRendererInterface::class)
                    : null
            ),
            LoginRequestHandler::class => static fn(ContainerInterface $container
            ): LoginRequestHandler => new LoginRequestHandler(
                $container->get(ResponseFactoryInterface::class),
                $container->has(TemplateRendererInterface::class)
                    ? $container->get(TemplateRendererInterface::class)
                    : null
            ),
        ];
    }

    /**
     * Returns a list of all container entries extended by this service provider.
     *
     * @return callable[]
     */
    public function getExtensions(): array
    {
        return [
            HttpRouteCollectorInterface::class => static function (
                ContainerInterface $container,
                HttpRouteCollectorInterface $routeCollector
            ): void {
                $loggedInUri = '/' . trim($container->get(static::CONFIG_URI_LOGGED_IN), '/');
                $loginUri = '/' . trim($container->get(static::CONFIG_URI_LOGIN), '/');
                // @TODO make deferred
                $authenticationMiddleware = $container->get(AuthenticationMiddleware::class)
                    ->withPasswordResolver($container->get(static::CONFIG_USER_RESOLVER_PASSWORD))
                    ->withLoggedInUri($loggedInUri)
                    ->withLoginUri($loginUri);
                $routeCollector->addRoute(
                    [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST],
                    $loginUri,
                    RequestHandler::withMiddleware(
                        $authenticationMiddleware,
                        RequestHandler::defer($container, LoginRequestHandler::class)
                    )
                );
                $routeCollector->get(
                    $loggedInUri . '{adminPath:.*}',
                    RequestHandler::withMiddleware(
                        $authenticationMiddleware,
                        RequestHandler::defer($container, AdminRequestHandler::class)
                    )
                );
            },
            TemplateRendererInterface::class => static function (
                ContainerInterface $container,
                TemplateRendererInterface $renderer
            ): void {
                $renderer->addPath(
                    dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
                    'templates',
                    'admin'
                );
                $adminData = [
                    'metaTitlePrefix' => $container->get('admin.meta.titlePrefix'),
                    'metaTitle' => $container->get('admin.meta.title'),
                    'metaTitleSuffix' => $container->get('admin.meta.titleSuffix'),
                ];
                $renderer->addTemplateData('admin::index', $adminData);
                $renderer->addTemplateData('admin::login', $adminData);
            },
        ];
    }
}
