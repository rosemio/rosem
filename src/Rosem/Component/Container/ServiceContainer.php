<?php
declare(strict_types=1);

namespace Rosem\Component\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Rosem\Component\Container\Exception;
use Rosem\Contract\Container\ServiceProviderInterface;

class ServiceContainer extends AbstractContainer
{
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
        parent::__construct();
        AbstractFacade::registerContainer($this);

        /** @var ServiceProviderInterface[] $serviceProviderInstances */
        $serviceProviderInstances = [];

        // 1. In the first pass, the container calls the getFactories method of all service providers.
        foreach ($serviceProviders as $serviceProvider) {
            if (\is_string($serviceProvider)) {
                if (class_exists($serviceProvider)) {
                    //TODO: exception
                    $serviceProviderInstances[] = $serviceProviderInstance = new $serviceProvider;

                    if ($serviceProviderInstance instanceof ServiceProviderInterface) {
                        $this->set($serviceProvider, static function () use ($serviceProviderInstance) {
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

    /**
     * Create container instance from array configuration.
     *
     * @param array $definitions
     *
     * @return self
     * @throws Exception\ContainerException
     */
    public static function fromArray(array $definitions): self
    {
        return new static($definitions);
    }

    /**
     * Create container instance from file configuration.
     *
     * @param string $filename
     *
     * @return self
     * @throws Exception\ContainerException
     * @throws \Exception
     */
    public static function fromFile(string $filename): self
    {
        return self::fromArray(self::getConfigurationFromFile($filename));
    }

    protected function set(string $id, $factory): void
    {
        $this->definitions[$id] = new Definition($factory);
    }

    protected function extend(string $id, $factory): void
    {
        $this->definitions[$id]->extend($factory);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    public function get($id)
    {
        if ($this->has($id)) {
            $definition = $this->definitions[$id];

            if ($definition instanceof Definition) {
                $value = $definition->create($this);

                if (null !== $value) {
                    return $this->definitions[$id] = $value;
                }

                if ($this->delegate !== null) {
                    return $this->delegate->get($id);
                }

                return Exception\ContainerException::notDefined($id);
            }

            return $definition;
        }

        if ($this->delegate !== null) {
            return $this->delegate->get($id);
        }

        return Exception\NotFoundException::notFound($id);
    }
}
