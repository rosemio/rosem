<?php

namespace Rosem\App\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psrnext\Config\ConfigInterface;
use Psrnext\Http\Factory\ResponseFactoryInterface;
use Psrnext\Template\TemplateRendererInterface;

class InternalServerErrorRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var TemplateRendererInterface
     */
    protected $view;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * MainController constructor.
     *
     * @param ResponseFactoryInterface  $responseFactory
     * @param TemplateRendererInterface $view
     * @param ConfigInterface           $config
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        TemplateRendererInterface $view,
        ConfigInterface $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->view = $view;
        $this->config = $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $body = $response->getBody();

        if ($body->isWritable()) {
            $viewString = $this->view->render(
                'app::500',
                [
                    'metaTitlePrefix' => $this->config->get('app.meta.title_prefix', ''),
                    'metaTitle'       => $this->config->get(
                        'app.meta.title',
                        $this->config->get('app.name', 'Rosem')
                    ),
                    'metaTitleSuffix' => $this->config->get('app.meta.title_suffix', ''),
                ]
            );

            if ($viewString) {
                $body->write($viewString);
            }
        }

        return $response;
    }
}
