<?php

declare(strict_types=1);

namespace Rosem\Component\Route\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface
};
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Rosem\Component\Route\Contract\RouteDispatcherInterface;

class RouteMiddleware implements MiddlewareInterface
{
    protected const KEY_STATUS = 0;

    protected const KEY_DATA = 1;

    protected const KEY_VARIABLES = 2;

    protected RouteDispatcherInterface $routeDispatcher;

    protected ResponseFactoryInterface $responseFactory;

    /**
     * @var string Attribute name for handler reference
     */
    protected string $attribute = RequestHandlerInterface::class;

    public function __construct(
        RouteDispatcherInterface $router,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->routeDispatcher = $router;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Set the attribute name to store handler reference.
     *
     * @param string $attribute
     *
     * @return RouteMiddleware
     */
    public function setAttribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $nextHandler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        //todo add scheme and host support
        $route = $this->routeDispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        switch ($route[static::KEY_STATUS]) {
            case RouteDispatcherInterface::NOT_FOUND:
                return $this->createNotFoundResponse();
            case RouteDispatcherInterface::SCOPE_NOT_ALLOWED:
                return $this->createMethodNotAllowedResponse()
                    ->withHeader('Access-Control-Allow-Methods', implode(', ', $route[1]));
        }

        foreach ($route[static::KEY_VARIABLES] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $this->setHandler($request, $route[static::KEY_DATA]);

        return $nextHandler->handle($request);
    }

    /**
     * Set the handler reference on the request.
     *
     * @param ServerRequestInterface $request
     * @param mixed                  $handler
     *
     * @return ServerRequestInterface
     */
    protected function setHandler(ServerRequestInterface $request, $handler): ServerRequestInterface
    {
        return $request->withAttribute($this->attribute, $handler);
    }

    public function createNotFoundResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse(StatusCode::STATUS_NOT_FOUND);
    }

    public function createMethodNotAllowedResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse(StatusCode::STATUS_METHOD_NOT_ALLOWED);
    }
}
