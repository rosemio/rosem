<?php

declare(strict_types=1);

namespace Rosem\Component\Http\Server;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface
};
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Rosem\Contract\Http\Server\GroupMiddlewareInterface;

/**
 * Class GroupMiddleware.
 * @TODO rewrite with SPLStack
 */
class GroupMiddleware implements GroupMiddlewareInterface
{
    /**
     * @var MiddlewareInterface
     */
    protected MiddlewareInterface $firstMiddleware;

    /**
     * @var MiddlewareInterface
     */
    protected MiddlewareInterface $lastMiddleware;

    /**
     * @var MiddlewareInterface
     */
    protected MiddlewareInterface $finalMiddleware;

    /**
     * @inheritDoc
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->firstMiddleware = isset($this->firstMiddleware)
            ? Middleware::withMiddleware($this->firstMiddleware, $middleware)
            : $middleware;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        return $this->firstMiddleware->process($request, $requestHandler);
    }
}
