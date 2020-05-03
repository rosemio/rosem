<?php

declare(strict_types=1);

namespace Rosem\Component\Route\Map;

use Rosem\Component\Route\Contract\{
    RouteDispatcherInterface,
    RouteParserInterface
};
use Rosem\Contract\Route\RouteCollectorInterface;

use function array_keys;
use function ltrim;
use function mb_strtoupper;
use function rtrim;
use function trim;

abstract class AbstractMap implements RouteCollectorInterface, RouteDispatcherInterface
{
    /**
     * Route pattern parser.
     *
     * @var RouteParserInterface
     */
    protected RouteParserInterface $parser;

    /**
     * Data of each static route in the collection.
     *
     * @var array[]
     */
    protected array $staticRouteMap = [];

    /**
     * Data of each variable route in the collection.
     *
     * @var array
     */
    protected array $variableRouteMap = [];

    /**
     * @var string
     */
    protected string $currentGroupPrefix = '';

    /**
     * A delimiter which is used for separation of route parts.
     *
     * @var string
     */
    private string $delimiter = '/';

    /**
     * Determine if a leading delimiter should be kept.
     *
     * @var bool
     */
    private bool $keepLeadingDelimiter = true;

    /**
     * Determine if a trailing delimiter should be kept.
     *
     * @var bool
     */
    private bool $keepTrailingDelimiter = false;

    /**
     * UTF-8 flag.
     *
     * @var bool
     */
    protected bool $utf8 = false;

    /**
     * AbstractRouteMap constructor.
     *
     * @param RouteParserInterface $parser
     */
    public function __construct(RouteParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Add variable route to the collection.
     *
     * @param string $scope
     * @param array  $parsedRoute
     * @param mixed  $resource
     */
    abstract protected function addVariableRoute(string $scope, array $parsedRoute, $resource): void;

    /**
     * Retrieve data associated with the route.
     *
     * @param array  $metaData
     * @param string $uri
     *
     * @return array
     */
    abstract protected function dispatchVariableRoute(array $metaData, string $uri): array;

    /**
     * Use UTF-8 or no.
     *
     * @param bool $use
     */
    public function useUtf8(bool $use = true): void
    {
        $this->utf8 = $use;
    }

    /**
     * @inheritDoc
     */
    public function addRoute($scopes, string $routePattern, $resource): void
    {
        $scopes = (array)$scopes;
        $routePattern = $this->currentGroupPrefix . $this->normalize($routePattern);

        foreach ($this->parser->parse($routePattern) as $parsedRoute) {
            if ($this->isStaticRoute($parsedRoute)) {
                foreach ($scopes as $scope) {
                    $this->addStaticRoute(mb_strtoupper($scope), $parsedRoute[0], $resource);
                }
            } else {
                foreach ($scopes as $scope) {
                    $this->addVariableRoute(mb_strtoupper($scope), [$routePattern, ...$parsedRoute], $resource);
                }
            }
        }
    }

    /**
     * Check if the given parsed route is a static route.
     *
     * @param array $parsedRoute
     *
     * @return bool
     */
    private function isStaticRoute(array $parsedRoute): bool
    {
        return $parsedRoute[1] === [];
    }

    /**
     * Add static route to the collection.
     *
     * @param string $scope
     * @param string $routePattern
     * @param mixed  $resource
     */
    private function addStaticRoute(string $scope, string $route, $resource): void
    {
        if (isset($this->staticRouteMap[$route][$scope])) {
            // todo required: throw an error for the same scope
        }

        // todo optimization: add reference if resource is same
        $this->staticRouteMap[$route][$scope] = $resource;
    }

    /**
     * @inheritDoc
     */
    public function addGroup(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * {@inheritDoc}
     * Returns array with one of the following formats:
     *     [
     *         StatusCodeInterface::STATUS_NOT_FOUND
     *     ]
     *     [
     *         StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
     *         [RequestMethodInterface::METHOD_GET, other allowed methods...]
     *     ]
     *     [
     *         StatusCodeInterface::STATUS_FOUND,
     *         $data,
     *         ['varName' => 'value', other variables...]
     *     ]
     *
     * @see \Fig\Http\Message\RequestMethodInterface
     * @see \Fig\Http\Message\StatusCodeInterface
     */
    public function dispatch(string $scope, string $uri): array
    {
        if (isset($this->staticRouteMap[$uri])) {
            $routeData = $this->staticRouteMap[$uri];

            if (isset($routeData[$scope])) {
                return [self::FOUND, $routeData[$scope], []];
            }

            // If there are no allowed methods the route simply does not exist
            return [self::SCOPE_NOT_ALLOWED, array_keys($routeData)];
        }

        // Find allowed scopes for this route by matching against all other scopes as well
        $allowedScopes = [];

        foreach ($this->variableRouteMapExpressions as $allowedScope => $metaData) {
            $routeData = $this->dispatchVariableRoute($metaData, $uri);

            if ($routeData[0] !== self::FOUND) {
                continue;
            }

            if ($scope === $allowedScope) {
                return $routeData;
            }

            $allowedScopes[] = $allowedScope;
        }

        // If there are no allowed scopes the route simply does not exist
        if ($allowedScopes !== []) {
            return [self::SCOPE_NOT_ALLOWED, $allowedScopes];
        }

        return [self::NOT_FOUND];
    }

    /**
     * Remove leading and / or trailing delimiter if configured to do it.
     *
     * @param string $route
     *
     * @return string
     */
    private function normalize(string $route): string
    {
        if ($route === $this->delimiter) {
            return $route;
        }

        if (!$this->keepLeadingDelimiter && !$this->keepTrailingDelimiter) {
            return trim($route, $this->delimiter);
        }

        if (!$this->keepLeadingDelimiter) {
            return ltrim($route, $this->delimiter);
        }

        if (!$this->keepTrailingDelimiter) {
            return rtrim($route, $this->delimiter);
        }

        return $route;
    }
}
