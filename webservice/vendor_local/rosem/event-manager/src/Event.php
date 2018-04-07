<?php

namespace Rosem\EventManager;

use Psrnext\EventManager\EventInterface;

class Event implements EventInterface
{
    /**
     * Event name.
     *
     * @var string
     */
    protected $name;

    /**
     * Target/context from which event was triggered.
     *
     * @var null|string|object
     */
    protected $target;

    /**
     * Parameters passed to the event.
     *
     * @var array
     */
    protected $params;

    /**
     * Flag to determine is propagation stopped.
     *
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * Get event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get target/context from which event was triggered.
     *
     * @return null|string|object
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Get parameters passed to the event.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get a single parameter by name.
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function getParam($name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * Set the event name.
     *
     * @param  string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the event target.
     *
     * @param  null|string|object $target
     *
     * @return void
     */
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * Set event parameters.
     *
     * @param  array $params
     *
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Indicate whether or not to stop propagating this event.
     *
     * @param  bool $flag
     *
     * @return void
     */
    public function stopPropagation(bool $flag): void
    {
        $this->propagationStopped = $flag;
    }

    /**
     * Has this event indicated event propagation should stop?
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
