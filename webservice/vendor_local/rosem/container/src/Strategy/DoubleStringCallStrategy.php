<?php

namespace Rosem\Container\Strategy;

use Rosem\Container\AbstractContainer;
use Rosem\Container\Exception\NotFoundException;

class DoubleStringCallStrategy
{
    protected $container;
    protected $containerDelegate;
    protected $autowiring;

    public function __construct(
        AbstractContainer $container,
        ?AbstractContainer $containerDelegate = null,
        bool $autowiring = false
    ) {
        $this->container = $container;
        $this->containerDelegate = $containerDelegate;
        $this->autowiring = $autowiring;
    }

    /**
     * @param string[] $abstract
     * @param array    $args
     *
     * @return mixed
     * @throws NotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function process(array $abstract, array $args)
    {
        if ($definition = $this->container->find(reset($abstract))) {
            return $definition->withMethodCall(next($abstract))->call(...$args);
        } elseif ($this->containerDelegate) {
            return $this->containerDelegate->call($abstract, ...$args);
        }

        if ($this->autowiring) {
            return $this->container->defineClassNow($abstract)->commit()
                ->withMethodCall(next($callable))->call(...$args);
        } else {
            throw new NotFoundException('Definition not found');
        }
    }

    public function make($abstract, array ...$args)
    {
        return $this->process($abstract, $args);
    }
}
