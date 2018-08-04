<?php

namespace Rosem\Admin;

use Psr\Container\ContainerInterface;
use Psrnext\Http\Factory\ResponseFactoryInterface;
use Psrnext\Template\TemplateRendererInterface;
use Rosem\Admin\Http\Server\AdminRequestHandler;
use Rosem\Admin\Http\Server\LoginRequestHandler;
use Psrnext\Config\ConfigInterface;
use Psrnext\Container\ServiceProviderInterface;
use Psrnext\Route\RouteCollectorInterface;
use Rosem\Authentication\Http\Server\AuthenticationMiddleware;

class AdminServiceProvider implements ServiceProviderInterface
{
    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * @return callable[]
     */
    public function getFactories(): array
    {
        return [
            'admin.meta.title_prefix' => function (ContainerInterface $container) {
                return ($container->has('app.name') ? $container->get('app.name') . ' ' : '') . 'Admin | ';
            },
            'admin.meta.title' => function () {
                return 'Welcome';
            },
            'admin.meta.title_suffix' => function () {
                return '';
            },
            'admin.uri' => function () {
                return '/admin';
            },
            'admin.loginUri' => function (ContainerInterface $container) {
                return $container->get('admin.uri') . '/login';
            },
            AdminRequestHandler::class => function (ContainerInterface $container) {
                return new AdminRequestHandler(
                    $container->get(ResponseFactoryInterface::class),
                    $container->get(TemplateRendererInterface::class),
                    $container->get(ConfigInterface::class)
                );
            },
            LoginRequestHandler::class => function (ContainerInterface $container) {
                return new LoginRequestHandler(
                    $container->get(ResponseFactoryInterface::class),
                    $container->get(TemplateRendererInterface::class),
                    $container->get(ConfigInterface::class)
                );
            },
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
            RouteCollectorInterface::class => function (
                ContainerInterface $container,
                RouteCollectorInterface $routeCollector
            ) {
                $adminUri = '/' . trim($container->get('admin.uri'), '/');
                $loginUri = '/' . trim($container->get('admin.loginUri'), '/');
                $authenticationOptions = [
                    AuthenticationMiddleware::getLoginUriAttribute() => $loginUri,
                ];
                $routeCollector->get($loginUri, LoginRequestHandler::class)
                    ->addMiddleware(AuthenticationMiddleware::class, $authenticationOptions);
                $routeCollector->post($loginUri, LoginRequestHandler::class)
                    ->addMiddleware(AuthenticationMiddleware::class, $authenticationOptions);
                $routeCollector->get($adminUri . '{adminRelativePath:.*}', AdminRequestHandler::class)
                    ->addMiddleware(AuthenticationMiddleware::class, $authenticationOptions);
            },
            TemplateRendererInterface::class => function (
                ContainerInterface $container,
                TemplateRendererInterface $renderer
            ) {
                $renderer->addPath(__DIR__ . '/resources/templates', 'admin');
                $adminData = [
                    'metaTitlePrefix' => $container->get('admin.meta.title_prefix'),
                    'metaTitle'       => $container->get('admin.meta.title'),
                    'metaTitleSuffix' => $container->get('admin.meta.title_suffix'),
                ];
                $renderer->addTemplateData('admin::index', $adminData);
                $renderer->addTemplateData('admin::login', $adminData);
            }
        ];
    }
}
