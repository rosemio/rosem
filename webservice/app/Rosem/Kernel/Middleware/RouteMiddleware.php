<?php

namespace Rosem\Kernel\Middleware;

use TrueStd\Http\Factory\{
    MiddlewareFactoryInterface, ResponseFactoryInterface
};
use TrueStd\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TrueStd\Http\Server\MiddlewareInterface;
use TrueStd\RouteCollector\RouteDispatcherInterface;

class RouteMiddleware implements MiddlewareInterface
{
    protected $router;

    protected $middlewareFactory;

    protected $responseFactory;

    public function __construct(
        RouteDispatcherInterface $router,
        MiddlewareFactoryInterface $middlewareFactory,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->router = $router;
        $this->middlewareFactory = $middlewareFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $nextHandler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler) : ResponseInterface
    {
        $route = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($route[0] === RouteDispatcherInterface::NOT_FOUND) {
            return $this->createNotFoundResponse();
        }

        if ($route[0] === RouteDispatcherInterface::METHOD_NOT_ALLOWED) {
            return $this->createMethodNotAllowedResponse();
        }

        foreach ($route[2] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $this->middlewareFactory->createMiddleware($route[1])->process($request, $nextHandler);
    }

    public function createNotFoundResponse() : ResponseInterface
    {
        $response = $this->responseFactory->createResponse(404);

        if ($response->getBody()->isWritable()) {
            $response->getBody()->write('Not found :(');
        }

        return $response;
    }

    public function createMethodNotAllowedResponse() : ResponseInterface
    {
        $response = $this->responseFactory->createResponse(405);

        if ($response->getBody()->isWritable()) {
            $response->getBody()->write('Method not allowed :(');
        }

        return $response;
    }
}
