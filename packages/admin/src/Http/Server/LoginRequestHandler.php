<?php

declare(strict_types=1);

namespace Rosem\Component\Admin\Http\Server;

use Psr\Http\Message\{
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestInterface
};
use Psr\Http\Server\RequestHandlerInterface;
use Rosem\Contract\Template\TemplateRendererInterface;

/**
 * Class LoginRequestHandler.
 */
class LoginRequestHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $responseFactory;

    protected TemplateRendererInterface $templateRenderer;

    /**
     * MainController constructor.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ?TemplateRendererInterface $templateRenderer
    ) {
        $this->responseFactory = $responseFactory;

        if ($templateRenderer !== null) {
            $this->templateRenderer = $templateRenderer;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();

        if (! isset($this->templateRenderer)) {
            return $response;
        }

        $body = $response->getBody();

        if ($body->isWritable()) {
            $viewString = $this->templateRenderer->render(
                'admin::login',
                [
                    'metaTitle' => 'Login',
                    'loginUri' => $request->getUri()->getPath(),
                ]
            );

            if ($viewString) {
                $body->write($viewString);
            }
        }

        return $response;
    }
}
