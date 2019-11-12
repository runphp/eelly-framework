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
use Illuminate\Config\Repository;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Exception\ExceptionHandler;
use Shadon\Exception\ServerException;
use function Shadon\Helper\isCli;
use function Shadon\Helper\realpath;
use Symfony\Component\Debug\ErrorHandler;

/**
 * Trait RuntimeTrait.
 *
 * hehui<runphp@qq.com>
 */
trait RuntimeTrait
{
    /**
     * Initialize runtime.
     *
     * @param string $rootPath
     *
     * @throws \Exception
     *
     * @return array
     */
    private function initRuntime(string $rootPath): array
    {
        $errorhandler = ErrorHandler::register();
        $this->initEnvironment($rootPath);
        $exceptionHandler = ExceptionHandler::register('develop' == APP['env']);
        if (\in_array(false, APP)) {
            throw new ServerException('error runtime, check `.env`');
        }

        return [$errorhandler, $exceptionHandler];
    }

    /**
     * Initiali app env.
     *
     * @param string $rootPath
     *
     * @throws \Exception
     */
    private function initEnvironment(string $rootPath): void
    {
        if (\defined('APP')) {
            return;
        }
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
            'serverName' => 'Shadon',
            'version'    => '2.0',
        ]);
    }

    /**
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     *
     * @return DI\Container
     */
    private function createContainer(ClassLoader $classLoader): DI\Container
    {
        $isCli = isCli();
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(realpath('var'), $isCli ? 'CompiledContainerConsole' : 'CompiledContainerFpm');
        $containerBuilder->writeProxiesToFile(true, realpath($isCli ? 'var/cache/console' : 'var/cache/fpm'));
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $config = (require realpath('var/config').($isCli ? '/console.php' : '/fpm.php')) + (require realpath('var/config/'.APP['env']).'/config.php');
        $definitions = $config['definitions'];
        unset($config['definitions']);
        $definitions += [
            // loader
            ClassLoader::class => $classLoader,
            // config
            'config' => new Repository($config),
        ];
        $containerBuilder->addDefinitions($definitions);

        return $containerBuilder->build();
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
        $di = $this->createContainer($classLoader);
        /* @var FpmContext $context */
        $context = $di->get(ContextInterface::class);
        $errorHandler->setDefaultLogger($di->get(LoggerInterface::class));
        $exceptionHandler->setContext($context);

        return $context;
    }
}
