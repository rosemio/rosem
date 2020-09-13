<?php

declare(strict_types=1);

namespace Rosem\Component\Route\Middleware;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface
};
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface
};
use Rosem\Component\Http\Server\{
    CallableBasedMiddleware,
    GroupMiddleware
};

use RuntimeException;
use function gettype;
use function is_callable;
use function is_string;
use function key;
use function reset;

class HandleRequestMiddleware implements MiddlewareInterface
{
    protected const KEY_HANDLER_OR_ALLOWED_METHODS = 0;

    protected const KEY_MIDDLEWARE = 1;

    /**
     * @var ContainerInterface used to resolve the handlers
     */
    private ContainerInterface $container;

    /**
     * @var string attribute name for handler reference
     */
    private string $handlerAttribute = RequestHandlerInterface::class;

    /**
     * RequestHandlerMiddleware constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Use this attribute name to store a handler reference.
     */
    public function withHandlerAttribute(string $handlerAttribute): self
    {
        $new = clone $this;
        $new->handlerAttribute = $handlerAttribute;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestData = $request->getAttribute($this->handlerAttribute);

        //@TODO improve if
        if ($requestData instanceof RequestHandlerInterface) {
            $requestHandler = $requestData;
        } else {
            $requestData = (array) $requestData;

            if (! empty($requestData[static::KEY_MIDDLEWARE])) {
                $requestHandler = new GroupMiddleware(
                    $this->container->get($requestData[static::KEY_HANDLER_OR_ALLOWED_METHODS])
                );

                /** @var array[] $requestData */
                foreach ($requestData[static::KEY_MIDDLEWARE] as $middlewareExtension) {
                    $requestHandler->addMiddleware(
                        is_callable($middlewareExtension)
                            ? $middlewareExtension($this->container)
                            : $this->container->get($middlewareExtension)
                    );
                }
            } else {
                $requestHandler = $this->container->get($requestData[static::KEY_HANDLER_OR_ALLOWED_METHODS]);
            }
        }

        if ($requestHandler instanceof RequestHandlerInterface) {
            return $requestHandler->handle($request);
        }

        if (is_callable($requestHandler)) {
            if (is_string(reset($requestHandler))) {
                $requestHandler[key($requestHandler)] = $this->container->get(reset($requestHandler));
            }

            return (new CallableBasedMiddleware(
                $this->container->get(ResponseFactoryInterface::class),
                $requestHandler
            )
            )->process($request, $handler);
        }

        throw new RuntimeException(sprintf('Invalid request handler: %s', gettype($requestHandler)));
    }
}
