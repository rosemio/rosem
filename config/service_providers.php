<?php

return [
    // FIXME: session provider
    Rosem\Authentication\AuthenticationProvider::class, // add session attr to the request
    Rosem\Route\RouteServiceProvider::class, // use session on admin route, add route and handler middleware
    // FIXME: auth provider
    Rosem\Admin\AdminServiceProvider::class, // add admin route
    Rosem\Template\TemplateServiceProvider::class,
    Rosem\App\AppServiceProvider::class, // add any route
    Rosem\GraphQL\GraphQLServiceProvider::class,
    Rosem\Access\Provider\AccessServiceProvider::class,
    Rosem\Database\DatabaseServiceProvider::class,
//    Rosem\Doctrine\Provider\ORMServiceProvider::class,
//    Rosem\Atlas\Provider\AtlasServiceProvider::class,
];
