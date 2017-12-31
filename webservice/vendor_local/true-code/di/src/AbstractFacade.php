<?php

namespace True\DI;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class AbstractFacade
 */
abstract class AbstractFacade
{
    /**
     * The container
     *
     * @var ContainerInterface $container
     */
    protected static $container;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstances;

    /**
     * Set the container instance.
     *
     * @param  ContainerInterface $container
     * @return void
     */
    final public static function registerContainer(ContainerInterface $container)
    {
        static::$container = $container;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string|object
     * @throws RuntimeException
     */
    protected abstract static function getFacadeAccessor();

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string|object $name
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected static function resolveFacadeInstance($name)
    {
        return is_object($name)
            ? $name
            : static::$resolvedInstances[$name]
            ?? (static::$resolvedInstances[$name] = static::$container->get($name));
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param  string $name
     */
    public static function clearResolvedFacadeInstance(string $name)
    {
        unset(static::$resolvedInstances[$name]);
    }

    /**
     * Clear all of the resolved facade instances.
     *
     * @return void
     */
    public static function clearResolvedFacadeInstances()
    {
        static::$resolvedInstances = [];
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     * @throws RuntimeException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    final public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
