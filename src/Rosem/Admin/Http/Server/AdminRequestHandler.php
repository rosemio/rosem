<?php

namespace Rosem\Admin\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psrnext\Config\ConfigInterface;
use Psrnext\Http\Factory\ResponseFactoryInterface;
use Psrnext\ViewRenderer\ViewRendererInterface;

class AdminRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var ViewRendererInterface
     */
    protected $view;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * MainController constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param ViewRendererInterface    $view
     * @param ConfigInterface       $config
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ViewRendererInterface $view,
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
                'admin::index',
                [
                    'metaTitlePrefix' => $this->config->get('admin.meta.title_prefix', ''),
                    'metaTitle'       => $this->config->get(
                        'admin.meta.title',
                        $this->config->get('app.name', 'Rosem')
                    ),
                    'metaTitleSuffix' => $this->config->get('admin.meta.title_suffix', ''),
                    'username' => $request->getAttribute('userIdentity'),
                ]
            );

            if ($viewString) {
                $body->write($viewString);
            }
        }

        return $response;
    }
}
