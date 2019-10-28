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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Exception\ExceptionHandler;
use function Shadon\Helper\realpath;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;

/**
 * ConsoleApplication run in php console.
 *
 * @author hehui<runphp@qq.com>
 */
class ConsoleApplication
{
    use RuntimeTrait;

    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     */
    public function __invoke(string $rootPath, ClassLoader $classLoader): void
    {
        $context = $this->registerService($classLoader, ...$this->initRuntime($rootPath));

        $app = new Application(APP['serverName'], APP['version']);
        $finder = new Finder();
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        foreach ($finder->in('src/Module/*/Console')->name('*.php') as $file) {
            /* @var \Symfony\Component\Finder\SplFileInfo $file */
            $stmts = $parser->parse($file->getContents());
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Namespace_) {
                    foreach ($stmt->stmts as $classStmt) {
                        if ($classStmt instanceof Class_) {
                            require $file->getRealPath();
                            $className = (string) $stmt->name.'\\'.$classStmt->name;
                            $app->add(new $className());
                            break;
                        }
                    }
                    break;
                }
            }
        }
        $dispatcher = new EventDispatcher();
        $app->setDispatcher($dispatcher);
        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($context, $classLoader): void {
            $command = $event->getCommand();
            // init module
            $class = new \ReflectionClass(\get_class($command));
            $namespace = $class->getNamespaceName();
            $prefix = substr($namespace, 0, -7);
            $classLoader->addPsr4($prefix, \dirname($class->getFileName(), 2));
            $module = $context->get($prefix.'Module');
            $module->init();
            // TODO
        });
        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event): void {
        });
        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event): void {
        });

        $app->run();
    }

    private function registerService(ClassLoader $classLoader, ErrorHandler $errorHandler, ExceptionHandler $exceptionHandler): ContextInterface
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(realpath('var'), 'CompiledContainerConsole');
        $containerBuilder->writeProxiesToFile(true, realpath('var/cache'));
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $config = (require realpath('var/config').'/console.php') + (require realpath('var/config/'.APP['env']).'/config.php');
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
        $exceptionHandler->setContext($context);

        return $context;
    }
}
