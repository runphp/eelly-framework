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
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Error\ExceptionHandler;
use Shadon\Exception\ServerException;
use function Shadon\Helper\realpath;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * FpmApplication run in php fpm.
 *
 * hehui<runphp@qq.com>.
 */
class FpmApplication
{
    /**
     * @var Di\Container
     */
    private $di;

    /**
     * MacroApplication constructor.
     *.
     *
     * @param string      $namespace
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     */
    public function __construct(string $rootPath, ClassLoader $classLoader)
    {
        $this->initRuntime($rootPath);
        $this->registerService($classLoader);
    }

    /**
     * @return int
     */
    public function run()
    {
        /* @var FpmContext $context */
        $context = $this->di->get(ContextInterface::class);
        $request = $context->get(Request::class);
        $dispatcher = FastRoute\simpleDispatcher($context->routeDefinitionCallback());
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
        $context->handle($routeInfo)->send();
    }

    /**
     * @param string $namespace
     * @param string $rootPath
     *
     * @throws \Exception
     */
    private function initRuntime(string $rootPath): void
    {
        ErrorHandler::register();
        // created default .env
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APP_KEY=/m',
                    'APP_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = \Dotenv\Dotenv::create($rootPath);
        $dotenv->load();
        \define('APP', [
            'env'        => getenv('APP_ENV'),
            'key'        => getenv('APP_KEY'),
            'namespace'  => getenv('NS'),
            'rootPath'   => $rootPath,
            'serverName' => 'Shadon/v2.0',
        ]);
        ExceptionHandler::register('develop' == APP['env'])->setDi($this->di);
        if (\in_array(false, APP)) {
            throw new ServerException('error runtime, check `.env`');
        }
    }

    private function registerService(ClassLoader $classLoader): void
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(realpath('var'));
        $containerBuilder->writeProxiesToFile(true, realpath('var/cache'));
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $config = (require realpath('var/config').'/config.php') + (require realpath('var/config/'.APP['env']).'/config.php');
        $definitions = $config['definitions'];
        unset($config['definitions']);
        $definitions += [
            // loader
            ClassLoader::class => $classLoader,
            // config
            'config' => new Repository($config),
        ];
        $containerBuilder->addDefinitions($definitions);
        $this->di = $containerBuilder->build();
    }
}
