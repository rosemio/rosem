<?php

namespace Rosem\GraphQL\Provider;

use GraphQL\Server\StandardServer;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use Psr\Container\ContainerInterface;
use Psrnext\{
    Container\ServiceProviderInterface, Environment\EnvironmentInterface, GraphQL\GraphInterface
};
use Psrnext\Config\ConfigInterface;
use Rosem\GraphQL\Middleware\GraphQLMiddleware;

class GraphQLServiceProvider implements ServiceProviderInterface
{
    /**
     * Returns a list of all container entries registered by this service provider.
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     * Factories have the following signature:
     *        function(\Psr\Container\ContainerInterface $container)
     * @return callable[]
     */
    public function getFactories(): array
    {
        return [
            'graphQLFieldResolver'   => function () {
                return function ($value, $args, $context, ResolveInfo $info) {
                    $method = 'get' . ucfirst($info->fieldName);

                    return $value->$method();
                };
            },
            GraphQLMiddleware::class => function (ContainerInterface $container) {
                $config = $container->get(ConfigInterface::class);
                $schema = $container->get(GraphInterface::class)
                    ->schema($config->get('api.schema', 'default'));
                $schemaConfig = SchemaConfig::create();

                if ($query = $schema->getQueryData()) {
                    $schemaConfig->setQuery(new ObjectType($query));
                }

                if ($mutation = $schema->getMutationData()) {
                    $schemaConfig->setMutation(new ObjectType($mutation));
                }

                if ($subscription = $schema->getSubscriptionData()) {
                    $schemaConfig->setSubscription(new ObjectType($subscription));
                }

                $serverConfig = [
                    'schema'  => new Schema($schemaConfig),
                    'context' => $container,
                ];

                if ($container->has('graphQLFieldResolver')) {
                    $serverConfig['fieldResolver'] = $container->get('graphQLFieldResolver');
                }

                return new GraphQLMiddleware(
                    new StandardServer($serverConfig),
                    $config->get('api.uri', '/graphql'),
                    $container->get(EnvironmentInterface::class)->isDevelopmentMode()
                );
            },
        ];
    }

    /**
     * Returns a list of all container entries extended by this service provider.
     * - the key is the entry name
     * - the value is a callable that will return the modified entry
     * Callables have the following signature:
     *        function(Psr\Container\ContainerInterface $container, $previous)
     *     or function(Psr\Container\ContainerInterface $container, $previous = null)
     * About factories parameters:
     * - the container (instance of `Psr\Container\ContainerInterface`)
     * - the entry to be extended. If the entry to be extended does not exist and the parameter is nullable, `null` will be passed.
     * @return callable[]
     */
    public function getExtensions(): array
    {
        return [];
    }
}
