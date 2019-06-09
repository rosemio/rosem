<?php

namespace Rosem\Component\Template;

use Psr\Container\ContainerInterface;
use Rosem\Contract\Container\ServiceProviderInterface;
use Rosem\Contract\Template\TemplateRendererInterface;

class TemplateServiceProvider implements ServiceProviderInterface
{
    /**
     * Root directory of templates.
     */
    public const CONFIG_PATHS_ROOT = 'template.paths.root';

    /**
     * Extension of templates' files.
     */
    public const CONFIG_EXTENSION = 'template.extension';

    /**
     * Returns a list of all container entries registered by this service provider.
     * @return callable[]
     */
    public function getFactories(): array
    {
        return [
            static::CONFIG_PATHS_ROOT => function () {
                return '';
            },
            static::CONFIG_EXTENSION => function () {
                return 'phtml';
            },
            TemplateRendererInterface::class => [static::class, 'createTemplateRenderer'],
        ];
    }

    /**
     * Returns a list of all container entries extended by this service provider.
     * @return callable[]
     */
    public function getExtensions(): array
    {
        return [];
    }

    public function createTemplateRenderer(ContainerInterface $container)
    {
        return new TemplateRenderer(
            $container->get(static::CONFIG_PATHS_ROOT),
            $container->get(static::CONFIG_EXTENSION)
        );
    }
}
