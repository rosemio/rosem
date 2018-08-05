<?php

namespace Rosem\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Rosem\Psr\Container\ServiceProviderInterface;

class Container implements ContainerInterface
{
    /**
     * @var DefinitionProxy[]
     */
    protected $definitions;

    /**
     * @var ContainerInterface
     */
    protected $delegate;

    /**
     * Container constructor.
     *
     * @param iterable $serviceProviders
     *
     * @throws \InvalidArgumentException
     * @throws Exception\ContainerException
     */
    public function __construct(iterable $serviceProviders)
    {
        AbstractFacade::registerContainer($this);

        /** @var ServiceProviderInterface[] $serviceProviderInstances */
        $serviceProviderInstances = [];

        // 1. In the first pass, the container calls the getFactories method of all service providers.
        foreach ($serviceProviders as $serviceProvider) {
            if (\is_string($serviceProvider)) {
                if (class_exists($serviceProvider)) {
                    $serviceProviderInstances[] = $serviceProviderInstance = new $serviceProvider; //TODO: exception

                    if ($serviceProviderInstance instanceof ServiceProviderInterface) {
                        $this->set($serviceProvider, function () use ($serviceProviderInstance) {
                            return $serviceProviderInstance;
                        });

                        foreach ($serviceProviderInstance->getFactories() as $key => $factory) {
                            $this->set($key, $factory);
                        }
                    } else {
                        Exception\ServiceProviderException::invalidInterface($serviceProvider);
                    }
                } else {
                    Exception\ServiceProviderException::doesNotExist($serviceProvider);
                }
            } else {
                Exception\ServiceProviderException::invalidType($serviceProvider);
            }
        }

        // 2. In the second pass, the container calls the getExtensions method of all service providers.
        foreach ($serviceProviderInstances as $serviceProviderInstance) {
            foreach ($serviceProviderInstance->getExtensions() as $key => $factory) {
                if ($this->has($key)) {
                    $this->extend($key, $factory);
                }
            }
        }
    }

    public function set(string $id, $factory): void
    {
        $placeholder = &$this->definitions[$id];
        $placeholder = new DefinitionProxy($this, $placeholder, $factory);
    }

    public function extend(string $id, $factory): void
    {
        $this->definitions[$id]->extend($factory);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @return mixed Entry.
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->definitions[$id]->get();
        }

        if ($this->delegate) {
            return $this->delegate->get($id);
        }

        throw new Exception\NotFoundException("$id definition not found.");
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    public function delegate(ContainerInterface $delegate): void
    {
        $this->delegate = $delegate;
    }
}
