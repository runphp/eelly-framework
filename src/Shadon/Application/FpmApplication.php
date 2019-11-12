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
use Shadon\Context\ContextInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * FpmApplication run in php fpm.
 *
 * hehui<runphp@qq.com>
 */
class FpmApplication
{
    use RuntimeTrait;

    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws DI\DependencyException
     * @throws DI\NotFoundException
     */
    public function __invoke(string $rootPath, ClassLoader $classLoader): void
    {
        $context = $this->registerService($classLoader, ...$this->initRuntime($rootPath));
        ini_set('display_errors', '0');
        $this->run($context);
    }

    /**
     * Run your php app.
     *
     * @param ContextInterface $context
     */
    private function run(ContextInterface $context): void
    {
        $request = $context->get(Request::class);
        $dispatcher = FastRoute\simpleDispatcher($context->routeDefinitionCallback());
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
        $context->handle($routeInfo)->send();
    }
}
