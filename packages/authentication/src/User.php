<?php

namespace Rosem\Component\Authentication;

use Rosem\Contract\Authentication\UserInterface;

/**
 * Generic implementation of UserInterface.
 * This implementation is modeled as immutable, to prevent propagation of
 * user state changes.
 * We recommend that any details injected are serializable.
 */
class User implements UserInterface
{
    /**
     * @var string
     */
    private string $identity;

    /**
     * @var string[]
     */
    private array $roles;

    /**
     * @var array
     */
    private array $details;

    public function __construct(string $identity, array $roles = [], array $details = [])
    {
        $this->identity = $identity;
        $this->roles = $roles;
        $this->details = $details;
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param mixed  $default Default value to return if no detail matching $name is discovered.
     * @param string $name    The name of a detail.
     *
     * @return mixed
     */
    public function getDetail(string $name, $default = null)
    {
        return $this->details[$name] ?? $default;
    }
}
