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
use Illuminate\Config\Repository;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Exception\ExceptionHandler;
use function Shadon\Helper\realpath;
use Symfony\Component\Debug\ErrorHandler;
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
        $this->run($this->registerService($classLoader, ...$this->initRuntime($rootPath)));
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

    /**
     * Register service.
     *
     * @param ClassLoader      $classLoader
     * @param ErrorHandler     $errorHandler
     * @param ExceptionHandler $exceptionHandler
     *
     * @throws DI\DependencyException
     * @throws DI\NotFoundException
     * @throws \Exception
     *
     * @return ContextInterface
     */
    private function registerService(ClassLoader $classLoader, ErrorHandler $errorHandler, ExceptionHandler $exceptionHandler): ContextInterface
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(realpath('var'), 'CompiledContainerFpm');
        $containerBuilder->writeProxiesToFile(true, realpath('var/cache/fpm'));
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $config = (require realpath('var/config').'/fpm.php') + (require realpath('var/config/'.APP['env']).'/config.php');
        $definitions = $config['definitions'];
        unset($config['definitions']);
        $definitions += [
            // loader
            ClassLoader::class => $classLoader,
            // config
            'config' => new Repository($config),
        ];
        $containerBuilder->addDefinitions($definitions);
        $di = $containerBuilder->build();
        /* @var FpmContext $context */
        $context = $di->get(ContextInterface::class);
        $errorHandler->setDefaultLogger($di->get(LoggerInterface::class));
        ini_set('display_errors', '0');
        $exceptionHandler->setContext($context);

        return $context;
    }
}
