<?php

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
use Rosem\Contract\Route\RouteDispatcherInterface;

class RouteMiddleware implements MiddlewareInterface
{
    protected const KEY_STATUS = 0;

    protected const KEY_HANDLER_OR_ALLOWED_METHODS = 1;

    protected const KEY_MIDDLEWARE = 2;

    protected const KEY_VARIABLES = 3;

    protected RouteDispatcherInterface $router;

    protected ResponseFactoryInterface $responseFactory;

    /**
     * @var string Attribute name for handler reference
     */
    protected string $attribute = RequestHandlerInterface::class;

    public function __construct(
        RouteDispatcherInterface $router,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->router = $router;
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
        $route = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($route[static::KEY_STATUS] === StatusCode::STATUS_NOT_FOUND) {
            return $this->createNotFoundResponse();
        }

        if ($route[static::KEY_STATUS] === StatusCode::STATUS_METHOD_NOT_ALLOWED) {
            return $this->createMethodNotAllowedResponse();
        }

        foreach ($route[static::KEY_VARIABLES] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $this->setHandler(
            $request,
            [$route[static::KEY_HANDLER_OR_ALLOWED_METHODS], $route[static::KEY_MIDDLEWARE]]
        );

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
        $response = $this->responseFactory->createResponse(StatusCode::STATUS_NOT_FOUND);
        $response->getBody()->write('Not found :(');

        return $response;
    }

    public function createMethodNotAllowedResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(StatusCode::STATUS_METHOD_NOT_ALLOWED);
        $response->getBody()->write('Method not allowed :(');

        return $response;
    }
}