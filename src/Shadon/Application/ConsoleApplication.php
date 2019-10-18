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
use MongoDB\BSON\ObjectId;
use Phalcon\Config;
use Shadon\Console\Application;
use Shadon\Console\Command\FlushCacheCommand;
use Shadon\Console\Command\HttpServerCommand;
use Shadon\Console\Command\QueueConsumerCommand;
use Shadon\Console\Command\TcpServerCommand;
use Shadon\Di\ConsoleDi;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class ConsoleApplication
{
    /**
     * @var Application
     */
    private $application;

    private $di;

    /**
     * ConsoleApplication constructor.
     *
     * @param ClassLoader $classLoader
     */
    public function __construct(ClassLoader $classLoader)
    {
        $this->di = new ConsoleDi();
        $this->di->setShared('loader', $classLoader);
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APPLICATION_KEY=/m',
                    'APPLICATION_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = new \Dotenv\Dotenv(getcwd(), '.env');
        $dotenv->load();
        $appEnv = getenv('APPLICATION_ENV');
        $appKey = getenv('APPLICATION_KEY');
        $arrayConfig = require 'var/config/config.'.$appEnv.'.php';
        // initialize constants and config
        \define('APP', [
            'env'       => $appEnv,
            'key'       => $appKey,
            'timezone'  => $arrayConfig['timezone'],
            'appname'   => $arrayConfig['appName'],
            'requestId' => (string) new ObjectId(),
        ]);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_CLI);
        $this->di->setShared('config', new Config($arrayConfig));
        date_default_timezone_set(APP['timezone']);
        $this->application = $this->di->getShared(Application::class, [ApplicationConst::APP_NAME, ApplicationConst::APP_VERSION]);
        /* @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->di->get('eventDispatcher');
        $eventDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event): void {
            ApplicationConst::setRequestAction($event->getCommand()->getName());
        });
        $this->application->setDispatcher($eventDispatcher);
    }

    public function handle()
    {
        /* @var \Monolog\Logger $logger */
        $logger = $this->di->getShared('errorLogger');
        \Monolog\ErrorHandler::register($logger);
        // register system commands
        $this->registerBaseCommands([
            FlushCacheCommand::class,
            HttpServerCommand::class,
            QueueConsumerCommand::class,
            TcpServerCommand::class,
        ]);
        // register modules commands
        $this->application->registerModulesCommands();

        return $this->application;
    }

    /**
     * run your application.
     */
    public function run(): void
    {
        $this->handle()->run();
    }

    /**
     * @param array $commands
     */
    private function registerBaseCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->application->add($this->di->getShared($command));
        }
    }
}
