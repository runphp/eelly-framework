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

namespace Shadon\Console\Command;

use Phalcon\Events\EventsAwareInterface;
use Shadon\Application\ApplicationConst;
use Shadon\Di\InjectionAwareInterface;
use Shadon\Di\Traits\InjectableTrait;
use Shadon\Network\HttpServer;
use Shadon\Process\HttpServerHealth;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HttpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    protected function configure(): void
    {
        $this->setName('api:httpserver')
            ->setDescription('Http server');
        $this->addOption('daemonize', '-d', InputOption::VALUE_NONE, '是否守护进程化');
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_SWOOLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $config = $this->getDI()->getShared('config');
        $httpServer = new HttpServer('0.0.0.0', $config['httpServer']['port']);
        $options = $config['httpServer']['swoole'];
        $options['pid_file'] = $config['httpServer']['pidFilePath'].'/httpserver.pid';
        $options['daemonize'] = $input->hasParameterOption(['--daemonize', '-d'], true);
        $httpServer->set($options->toArray());
        $httpServer->setDi($this->getDI());
        $httpServer->setOutput($output);
        $httpServer->addProcess(new HttpServerHealth($httpServer));
        $httpServer->start();
    }
}
