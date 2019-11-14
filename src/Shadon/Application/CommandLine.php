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
use Shadon\Command\ConsumeCommand;
use Shadon\Command\ConsumeCommandFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;

/**
 * Class CommandLine.
 *
 * @author hehui<runphp@qq.com>
 */
class CommandLine extends Application
{
    use AppTrait;

    public function __construct(string $rootPath, ClassLoader $classLoader)
    {
        $context = self::createContext($rootPath, $classLoader);
        parent::__construct(APP['serverName'].' Application', APP['version']);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($context, $classLoader): void {
            $command = $event->getCommand();
            if ($command instanceof ConsumeCommand) {
                // init module
                $moduleName = $command->getModuleName();
                $context->set('module', $moduleName);
                $prefix = APP['namespace'].'\\Module\\'.ucfirst($moduleName).'\\';
                $classLoader->addPsr4($prefix, \Shadon\Helper\realpath('src/Module/'.ucfirst($moduleName)));
                $module = $context->get($prefix.'Module');
                $module->init();
                $context->injectOn($command);
            }
        });
        $this->setDispatcher($eventDispatcher);
        $factories = [];
        foreach ($context->get('config')->get('moduleList') as $module) {
            // add module consume command
            $factories[$module.':consume'] = function () use ($module) {
                return ConsumeCommandFactory::create($module);
            };
        }
        $this->setCommandLoader(new FactoryCommandLoader($factories));
        $this->attachModuleCommands();
    }

    private function attachModuleCommands(): void
    {
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
                            $this->add(new $className());
                            break;
                        }
                    }
                    break;
                }
            }
        }
    }
}
