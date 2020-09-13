<?php

declare(strict_types=1);

namespace Rosem\Component\Http\Server;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Rosem\Component\Http\Server\Exception\InvalidEmitterException;
use Rosem\Contract\Http\Server\EmitterInterface;
use SplStack;

/**
 * Provides an EmitterInterface implementation that acts as a stack of Emitters.
 * The implementations emit() method iterates itself.
 * When iterating the stack, the first emitter to return a boolean
 * true value will short-circuit iteration.
 */
class EmitterStack extends SplStack implements EmitterInterface
{
    /**
     * Emit a response
     * Loops through the stack, calling emit() on each; any that return a
     * boolean true value will short-circuit, skipping any remaining emitters
     * in the stack.
     * As such, return a boolean false value from an emitter to indicate it
     * cannot emit the response, allowing the next emitter to try.
     */
    public function emit(ResponseInterface $response): bool
    {
        foreach ($this as $emitter) {
            if ($emitter->emit($response) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set an emitter on the stack by index.
     *
     * @param mixed $index
     * @param EmitterInterface $emitter
     *
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function offsetSet($index, $emitter): void
    {
        self::assertValidEmitter($emitter);

        parent::offsetSet($index, $emitter);
    }

    /**
     * Push an emitter to the stack.
     *
     * @param EmitterInterface $emitter
     *
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function push($emitter): void
    {
        self::assertValidEmitter($emitter);

        parent::push($emitter);
    }

    /**
     * Unshift an emitter to the stack.
     *
     * @param EmitterInterface $emitter
     *
     * @throws InvalidArgumentException if not an EmitterInterface instance
     */
    public function unshift($emitter): void
    {
        self::assertValidEmitter($emitter);

        parent::unshift($emitter);
    }

    /**
     * Validate that an emitter implements EmitterInterface.
     *
     * @param mixed $emitter
     *
     * @throws InvalidEmitterException for non-emitter instances
     */
    public static function assertValidEmitter($emitter): void
    {
        if (! $emitter instanceof EmitterInterface) {
            throw InvalidEmitterException::forEmitter($emitter);
        }
    }
}
