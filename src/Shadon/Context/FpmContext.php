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

use FastRoute;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shadon\Events\BeforeResponseEvent;
use Shadon\Exception\MethodNotAllowedException;
use Shadon\Exception\NotFoundException;
use Zend\Diactoros\StreamFactory;

/**
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    use ContextTrait;

    public function routeDefinitionCallback(): callable
    {
        return function (FastRoute\RouteCollector $routeCollector): void {
            // home
            $routeCollector->addRoute('GET', '/', function () {
                return 'Hello, I\'m '.APP['serverName'];
            });
            // internal api
            $routeCollector->addRoute(
                'develop' == APP['env'] ? ['GET', 'POST'] : 'POST',
                '/{module:[a-z][a-zA-Z]*}/internal/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}',
                function (string $module, string $controller, string $action) {
                    return $this->get(McaHandler::class)()($module, $controller, $action, 'Internal');
                });
            // open api
            $routeCollector->addRoute(
                'develop' == APP['env'] ? ['GET', 'POST'] : 'POST',
                '/{module:[a-z][a-zA-Z]*}/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}',
                $this->get(McaHandler::class)()
            );
        };
    }

    public function handle(array $routeInfo): ResponseInterface
    {
        if (FastRoute\Dispatcher::FOUND == $routeInfo[0]) {
            $this->set('return', $routeInfo[1](...array_values($routeInfo[2])));
        } elseif (FastRoute\Dispatcher::NOT_FOUND == $routeInfo[0]) {
            throw new NotFoundException(
                sprintf('api `%s` not found', $this->get(ServerRequestInterface::class)->getUri()->getPath())
            );
        } elseif (FastRoute\Dispatcher::METHOD_NOT_ALLOWED == $routeInfo[0]) {
            $request = $this->get(ServerRequestInterface::class);
            throw new MethodNotAllowedException(
                sprintf('api `%s` method `%s` not allowed', $request->getUri()->getPath(), $request->getMethod())
            );
        }
        // ready for response
        /* @var Dispatcher $dispatcher */
        $dispatcher = $this->get(Dispatcher::class);
        /* @var \Zend\Diactoros\Response\JsonResponse $response*/
        $response = $this->get(ResponseInterface::class);
        $dispatcher->dispatch(new BeforeResponseEvent($this));

        return $response->withBody((new StreamFactory())->createStream(json_encode($this->get('return'))));
    }
}
