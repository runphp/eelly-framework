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
use Shadon\Network\TcpServer;
use Shadon\Process\TcpServerHealth;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TcpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    protected function configure(): void
    {
        $this->setName('api:tcpserver')
            ->setDescription('Tcp server');
        $this->addArgument('module', InputArgument::REQUIRED, '模块名，如: example');
        $this->addOption('daemonize', '-d', InputOption::VALUE_NONE, '是否守护进程化');
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, '监听端口', 0);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_SWOOLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $tcpServer = new TcpServer('0.0.0.0', (int) $input->getOption('port'));
        $module = (string) $input->getArgument('module');
        $config = require 'var/config/'.APP['env'].'/'.$module.'/tcpServer.php';
        $options = $config['swoole'];
        $options['pid_file'] = $config['pidFilePath'].'/tcpserver_'.$module.'.pid';
        $options['daemonize'] = $input->hasParameterOption(['--daemonize', '-d'], true);
        $options['open_eof_check'] = true; //打开EOF检测
        $options['package_eof'] = "\r\n"; //设置EOF
        $tcpServer->set($options);
        $tcpServer->setDi($this->getDI());
        $tcpServer->setModuleName($module);
        $tcpServer->setOutput($output);
        $tcpServer->addProcess(new TcpServerHealth($tcpServer));
        $tcpServer->start();
    }
}
