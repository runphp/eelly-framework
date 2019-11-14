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

namespace Shadon\Application;

use Composer\Autoload\ClassLoader;
use DI;
use FastRoute;
use Psr\Http\Message\ServerRequestInterface;
use function Shadon\Helper\createContext;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

/**
 * FpmApplication run in php fpm.
 *
 * hehui<runphp@qq.com>
 */
class FpmApplication
{
    use AppTrait;

    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws DI\DependencyException
     * @throws DI\NotFoundException
     */
    public function __invoke(string $rootPath, ClassLoader $classLoader): void
    {
        $context = self::createContext($rootPath, $classLoader);
        // run
        $request = $context->get(ServerRequestInterface::class);
        $dispatcher = FastRoute\simpleDispatcher($context->routeDefinitionCallback());
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        $response = $context->handle($routeInfo);
        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }
}
