<?php

namespace TrueCode\RouteCollector;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\{
    CharCountBased, GroupCountBased, GroupPosBased, MarkBased
};
use TrueStd\RouteCollector\RouteDispatcherInterface;

class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * Char count based type.
     */
    const DRIVER_CHAR_COUNT = 'char_count';

    /**
     * Group count based type.
     */
    const DRIVER_GROUP_COUNT = 'group_count';

    /**
     * Group pos based type.
     */
    const DRIVER_GROUP_POS = 'group_pos';

    /**
     * Mark based type.
     */
    const DRIVER_MARK = 'mark';

    /**
     * @var Dispatcher
     */
    protected $driver;

    public function __construct(array $data, string $driverType = self::DRIVER_GROUP_COUNT)
    {
        switch ($driverType) {
            case self::DRIVER_CHAR_COUNT:
                $dispatcher = CharCountBased::class;
                break;
            case self::DRIVER_GROUP_POS:
                $dispatcher = GroupPosBased::class;
                break;
            case self::DRIVER_MARK:
                $dispatcher = MarkBased::class;
                break;
            case self::DRIVER_GROUP_COUNT:
            default:
                $dispatcher = GroupCountBased::class;
        }

        $this->driver = new $dispatcher($data);
    }

    /**
     * Dispatches against the provided HTTP method verb and URI.
     * Returns array with one of the following formats:
     *     [self::NOT_FOUND]
     *     [self::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
     *     [self::FOUND, $handler, ['varName' => 'value', ...]]
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array
     */
    public function dispatch(string $httpMethod, string $uri) : array
    {
        return $this->driver->dispatch($httpMethod, $uri);
    }
}
