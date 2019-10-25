<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shadon\Context;

use DI\Container;
use FastRoute;
use Illuminate\Contracts\Events\Dispatcher;
use Shadon\Events\BeforeResponseEvent;
use Shadon\Exception\MethodNotAllowedException;
use Shadon\Exception\NotFoundException;
use SplStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    use ContextTrait;

    /**
     * @var Container
     */
    private $di;

    /**
     * map.
     *
     * @var array
     */
    private $entries = [];

    /**
     * @var SplStack
     */
    private $handlerStack;

    public function __construct()
    {
        $this->handlerStack = new SplStack();
    }

    public function push(callable $handler): void
    {
        $this->handlerStack->push($handler);
    }

    public function next()
    {
        $handler = $this->handlerStack->shift();

        return $handler($this);
    }

    public function get($name)
    {
        if (isset($this->entries[$name])) {
            return $this->entries[$name];
        } else {
            return $this->di->get($name);
        }
    }

    public function set(string $name, $value): void
    {
        $this->entries[$name] = $value;
    }

    public function routeDefinitionCallback(): callable
    {
        return function (FastRoute\RouteCollector $routeCollector): void {
            $routeCollector->addRoute('GET', '/', function () {
                return 'Hello, I\'m '.APP['serverName'];
            });
            $routeCollector->addRoute(
                'develop' == APP['env'] ? ['GET', 'POST'] : 'POST',
                '/{module:[a-z][a-zA-Z]*}/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}',
                (new McaHandler())($this)
            );
        };
    }

    public function handle(array $routeInfo): Response
    {
        if (FastRoute\Dispatcher::FOUND == $routeInfo[0]) {
            $data = $routeInfo[1](...array_values($routeInfo[2]));
        } elseif (FastRoute\Dispatcher::NOT_FOUND == $routeInfo[0]) {
            throw new NotFoundException('check request url');
        } elseif (FastRoute\Dispatcher::METHOD_NOT_ALLOWED == $routeInfo[0]) {
            throw new MethodNotAllowedException('check method');
        }
        // ready for response
        /* @var Dispatcher $dispatcher */
        $dispatcher = $this->get(Dispatcher::class);
        $response = $this->get(Response::class);
        $dispatcher->dispatch(new BeforeResponseEvent($this, $data));

        return $response;
    }
}
