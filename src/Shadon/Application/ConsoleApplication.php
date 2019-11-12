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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Exception\ExceptionHandler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
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
     * @var Application
     */
    private $app;

    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     */
    public function __invoke(string $rootPath, ClassLoader $classLoader): void
    {
        $context = $this->registerService($classLoader, ...$this->initRuntime($rootPath));

        $this->app = new Application(APP['serverName'], APP['version']);
        $finder = new Finder();
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        foreach ($finder->in('src/Module/*/Command')->name('*Command.php') as $file) {
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
        $this->addEvents($classLoader, $context);

        $app->run();
    }

    private function registerService(ClassLoader $classLoader, ErrorHandler $errorHandler, ExceptionHandler $exceptionHandler): ContextInterface
    {
        $di = $this->createContainer($classLoader);
        /* @var FpmContext $context */
        $context = $di->get(ContextInterface::class);
        $errorHandler->setDefaultLogger($di->get(LoggerInterface::class));
        $exceptionHandler->setContext($context);

        return $context;
    }

    /**
     * @param ClassLoader      $classLoader
     * @param ContextInterface $context
     */
    private function addEvents(ClassLoader $classLoader, ContextInterface $context): void
    {
        $dispatcher = new EventDispatcher();
        $this->app->setDispatcher($dispatcher);
        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($context, $classLoader): void {
            $command = $event->getCommand();
            if ($command instanceof ListCommand || $command instanceof HelpCommand) {
                return;
            }
            // init module
            $class = new \ReflectionClass(\get_class($command));
            $namespace = $class->getNamespaceName();
            $moduleName = substr($namespace, 11, -8);
            $context->set('module', lcfirst($moduleName));
            $prefix = substr($namespace, 0, -7);
            $classLoader->addPsr4($prefix, \dirname($class->getFileName(), 2));
            $module = $context->get($prefix.'Module');
            $module->init();
            $context->injectOn($command);
        });
    }
}
