<?php

namespace Rosem\Route\Http\Server;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};
use Psr\Http\Server\{
    MiddlewareInterface, RequestHandlerInterface
};
use Psr\Http\Message\ResponseFactoryInterface;
use Rosem\Http\Server\{
    CallableBasedMiddleware, DeferredConfigurableMiddleware, MiddlewareQueue
};

class HandleRequestMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface used to resolve the handlers
     */
    private $container;

    /**
     * @var string attribute name for handler reference
     */
    private $handlerAttribute = 'requestHandler';

    /**
     * RequestHandlerMiddleware constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Set the attribute name to store handler reference.
     * @param string $handlerAttribute
     * @return self
     */
    public function handlerAttribute(string $handlerAttribute): self
    {
        $this->handlerAttribute = $handlerAttribute;

        return $this;
    }

    /**
     * Process a server request and return a response.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestData = $request->getAttribute($this->handlerAttribute);

        if (!empty($requestData[1])) { // TODO: add constants
            $requestHandler = new MiddlewareQueue($this->container, $this->container->get($requestData[0]));

            /** @var array[] $requestData */
            foreach ($requestData[1] as $middlewareData) {
                foreach ($middlewareData[1] as $param => $value) { // TODO: make it as middleware params
                    $request = $request->withAttribute($param, $value);
                }

                $requestHandler->use($middlewareData[0]);
            }
        } else {
            $requestHandler = $this->container->get($requestData[0]);
        }

        if ($requestHandler instanceof RequestHandlerInterface) {
            return $requestHandler->handle($request);
        }

        if (\is_callable($requestHandler)) {
            if (\is_string(reset($requestHandler))) {
                $requestHandler[key($requestHandler)] = $this->container->get(reset($requestHandler));
            }

            return (new CallableBasedMiddleware(
                $this->container->get(ResponseFactoryInterface::class),
                $requestHandler)
            )->process($request, $handler);
        }

        throw new \RuntimeException(sprintf('Invalid request handler: %s', \gettype($requestHandler)));
    }
}
