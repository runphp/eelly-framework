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

use InvalidArgumentException;
use Monolog\Logger;
use Phalcon\Events\EventsAwareInterface;
use Shadon\Di\InjectionAwareInterface;
use Shadon\Di\Traits\InjectableTrait;
use Shadon\Process\Process;
use Shadon\Queue\Adapter\Consumer;
use Shadon\Utils\DateTime;
use Swoole\Atomic;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 队列消费者.
 *
 * 该命令支持3个参数，示例如下：
 *
 * 选项 --count 可以指定消费者数量
 * ```
 * // 异步任务的消费，默认路由消费
 * bin/console queue-consumer logger
 *
 * // 对指定路由key进行消费
 * bin/console queue-consumer logger routing_key_name
 *
 * // 对指定路由key和队列进行消费
 * bin/console queue-consumer logger routing_key_name queue_name
 * ```
 *
 * @author hehui<hehui@eelly.net>
 */
class QueueConsumerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    /**
     * @var array
     */
    private $workers = [];

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Atomic
     */
    private $atomic;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('api:queue-consumer')
            ->setDescription('Queue consumer')
            ->setHelp('队列消费者');

        $this->addArgument('exchange', InputArgument::REQUIRED, '交换机名，系统设计为你的模块名，例如: logger');
        $this->addOption('--routingKey', null, InputOption::VALUE_OPTIONAL, '路由key', 'default_routing_key');
        $this->addOption('--queue', null, InputOption::VALUE_OPTIONAL, '队列名', 'default_queue');
        $this->addOption('--count', null, InputOption::VALUE_OPTIONAL, '消费者数量', 5);
        $this->addOption('daemonize', '-d', InputOption::VALUE_NONE, '是否守护进程化');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // define('AMQP_DEBUG', true);
        $this->input = $input;
        $this->output = $output;
        $this->atomic = new Atomic();
        if ($input->hasParameterOption(['--daemonize', '-d'], true)) {
            \swoole_process::daemon();
        }
        $this->waitProcess();
        $this->createProcess();
    }

    private function createProcess(): void
    {
        $count = (int) $this->input->getOption('count');
        for ($i = 0; $i < $count; $i++) {
            $this->createConsumerProcess($i);
        }
    }

    /**
     * @param int $index
     *
     * @return Process|__anonymous@2630
     */
    private function createConsumerProcess(int $index)
    {
        $process = new class(function (Process $worker) use ($index): void {
            $worker->setDi($this->di);
            $worker->setLogger($this->di->getShared('errorLogger'));
            $exchange = $this->input->getArgument('exchange');
            $routingKey = $this->input->getOption('routingKey');
            $queue = $this->input->getOption('queue');
            /* @var \Shadon\Queue\Adapter\Consumer $consumer */
            $consumer = $worker->createConsumer($exchange, $routingKey, $queue);
            $processName = $consumer->getQueueOptions()['name'].'#'.$index;
            $worker->name($processName);
            $pid = getmypid();
            $worker->write(sprintf('%s %d -1 "worker %s"', DateTime::formatTime(), $pid, $processName));
            while (true) {
                try {
                    $consumer->consume(100);
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    $worker->write(sprintf('%s %d -1 "%s line %s %s"', DateTime::formatTime(), $pid, \get_class($e), __LINE__, $e->getMessage()));
                    $connection = $consumer->getConnection();
                    $connection->reconnect();
                } catch (\PhpAmqpLib\Exception\AMQPRuntimeException | \PhpAmqpLib\Exception\AMQPProtocolException $e) {
                    $worker->write(sprintf('%s %d -1 "%s line %s %s"', DateTime::formatTime(), $pid, \get_class($e), __LINE__, $e->getMessage()));
                    $consumer = $worker->createConsumer($exchange, $routingKey, $queue);
                } catch (\Throwable $e) {
                    $worker->write(sprintf('%s %d -1 "%s line %s %s"', DateTime::formatTime(), $pid, \get_class($e), __LINE__, $e->getMessage()));
                    $this->di->getShared('errorLogger')->error('UncaughtException', [
                        'file'  => $e->getFile(),
                        'line'  => $e->getLine(),
                        'class' => \get_class($e),
                        'args'  => [
                            $e->getMessage(),
                        ],
                    ]);
                }
            }
        }) extends Process{
            /**
             * @var \Phalcon\Di
             */
            private $di;

            /**
             * @var \Monolog\Logger
             */
            private $logger;

            /**
             * @var Atomic
             */
            private $atomic;

            public function setDi($di): void
            {
                $this->di = $di;
            }

            public function setLogger($logger): void
            {
                $this->logger = $logger;
            }

            public function setAtomic(Atomic $atomic): void
            {
                $this->atomic = $atomic;
            }

            public function createConsumer($exchange, $routingKey, $queue)
            {
                $moduleName = ucfirst($exchange).'\\Module';
                if (!class_exists($moduleName)) {
                    throw new InvalidArgumentException('Not found exchange: '.$exchange);
                }
                /**
                 * @var \Shadon\Mvc\AbstractModule
                 */
                $moduleObject = $this->di->getShared($moduleName);
                /*
                     * 'registerAutoloaders' and 'registerServices' are automatically called
                     */
                $moduleObject->registerAutoloaders($this->di);
                $moduleObject->registerServices($this->di);
                /* @var \Shadon\Queue\Adapter\Consumer $consumer */
                $queueFactory = $this->di->get('queueFactory');
                $consumer = $queueFactory->createConsumer();
                $consumer->setQos([
                    'prefetch_size'  => 0,
                    'prefetch_count' => 1,
                    'global'         => false,
                ]);
                $consumer->setExchangeOptions(['name' => $exchange, 'type' => 'topic']);
                $consumer->setRoutingKey($routingKey);
                $consumer->setQueueOptions(['name' => $exchange.'.'.$routingKey.'.'.$queue]);

                $consumer->setCallback(
                    function ($msgBody): void {
                        try {
                            $msg = \GuzzleHttp\json_decode($msgBody, true);
                            $this->consumerCallback($msg);
                        } catch (\InvalidArgumentException $e) {
                            $this->logger->info($e->getMessage(), [$msgBody]);
                        }
                    }
                );

                return $consumer;
            }

            /**
             * @param array $msg
             */
            private function consumerCallback(array $msg): void
            {
                try {
                    $object = $this->di->getShared($msg['class']);
                } catch (\Phalcon\Di\Exception $e) {
                    $this->logger->warning($e->getMessage(), $msg);

                    return;
                }
                if (!method_exists($object, $msg['method'])) {
                    $this->logger->warning('Error method', $msg);

                    return;
                }
                $pid = getmypid();
                $num = $this->atomic->add(1);
                $this->write(sprintf('%s %d %d "%s::%s()" start', DateTime::formatTime(), $pid, $num, $msg['class'], $msg['method']));
                $start = microtime(true);
                $return = null;

                try {
                    $return = \call_user_func_array([$object, $msg['method']], $msg['params']);
                } catch (\TypeError | \LogicException $e) {
                    $this->logger->warning($e->getMessage(), [$msg, $e->getTraceAsString()]);
                } catch (\Throwable $e) {
                    $this->logger->error($e->getMessage(), [$msg, $e->getTraceAsString()]);

                    throw $e;
                }
                $usedTime = microtime(true) - $start;
                $this->write(sprintf('%s %d %d "%s::%s()" "%s" %s', DateTime::formatTime(), $pid, $num, $msg['class'], $msg['method'], json_encode($return), $usedTime));
            }
        };
        $process->setAtomic($this->atomic);
        swoole_event_add($process->pipe, function ($pipe) use ($process): void {
            $this->output->writeln($process->read());
        });
        $pid = $process->start();
        if (false === $pid) {
            $errorNo = swoole_errno();
            $errorStr = swoole_strerror($errorNo);
            $this->logger->error("swoole error($errorNo) $errorStr");
            $this->createConsumerProcess($index);
        } else {
            $this->workers[$index] = $pid;
        }
    }

    private function waitProcess(): void
    {
        \swoole_process::signal(SIGCHLD, function ($signo): void {
            while ($status = \swoole_process::wait(false)) {
                $index = array_search($status['pid'], $this->workers);
                $this->createConsumerProcess($index);
            }
        });
    }
}
