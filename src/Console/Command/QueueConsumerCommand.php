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
     * 警告消费时间(秒).
     *
     * @var int
     */
    public const WARNING_USED_TIME = 5;

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
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var string
     */
    private $queue;

    public function onWorkerStart(\Swoole\Process\Pool $pool, int $workerId): void
    {
        $processName = sprintf('%s.%s.%s#%d', $this->exchange, $this->routingKey, $this->queue, $workerId);
        \swoole_set_process_name($processName);
        $this->output->writeln($processName);
        $consumer = $this->createConsumer();
        while (true) {
            try {
                $consumer->consume(100);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // continue
            } catch (\PhpAmqpLib\Exception\AMQPExceptionInterface $e) {
                $consumer = $this->createConsumer();
            } catch (\Throwable $e) {
                $this->errorLogger->error('UncaughtException', [
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'class' => \get_class($e),
                    'args'  => [
                        $e->getMessage(),
                    ],
                ]);
            }
            if (isset($e)) {
                $this->output->writeln(sprintf('%s %d -1 "%s line %s %s"', DateTime::formatTime(), $pid, \get_class($e), __LINE__, $e->getMessage()));
            }
        }
    }

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
        $this->exchange = $this->input->getArgument('exchange');
        $this->routingKey = $this->input->getOption('routingKey');
        $this->queue = $this->input->getOption('queue');
        if ($input->hasParameterOption(['--daemonize', '-d'], true)) {
            \swoole_process::daemon();
        }
        swoole_set_process_name(sprintf('%s.%s.%s#%d', $this->exchange, $this->routingKey, $this->queue, -1));

        $count = (int) $this->input->getOption('count');
        $pool = new \Swoole\Process\Pool($count);
        $pool->on('workerStart', [$this, 'onWorkerStart']);
        $pool->start();
    }

    /**
     * @param mixed $msg
     */
    protected function consumerCallback($msg): void
    {
        if (!\is_array($msg) || !isset($msg['class'], $msg['method'], $msg['params'])) {
            $this->output->writeln(sprintf('%s %d -1 error msg "%s"', DateTime::formatTime(), getmypid(), json_encode($msg)));

            return;
        }
        try {
            $object = $this->di->getShared($msg['class']);
        } catch (\Phalcon\Di\Exception $e) {
            $this->errorLogger->warning($e->getMessage(), $msg);

            return;
        }
        if (!method_exists($object, $msg['method'])) {
            $this->errorLogger->warning('Error method', $msg);

            return;
        }
        $pid = getmypid();
        $num = $this->atomic->add(1);
        $this->output->writeln(sprintf('%s %d %d "%s::%s()" start', DateTime::formatTime(), $pid, $num, $msg['class'], $msg['method']));
        $start = microtime(true);
        $return = null;

        try {
            $return = \call_user_func_array([$object, $msg['method']], $msg['params']);
        } catch (\TypeError | \LogicException $e) {
            $this->errorLogger->warning($e->getMessage(), [$msg, $e->getTraceAsString()]);
        } catch (\Throwable $e) {
            $this->errorLogger->error($e->getMessage(), [$msg, $e->getTraceAsString()]);

            throw $e;
        }
        $usedTime = microtime(true) - $start;
        if (5 < $usedTime) {
            $this->errorLogger->warning('Occur slow consumer', ['pid' => $pid, 'used' => $usedTime, 'msg' => $msg]);
        }
        $this->output->writeln(sprintf('%s %d %d "%s::%s()" "%s" %s', DateTime::formatTime(), $pid, $num, $msg['class'], $msg['method'], json_encode($return), $usedTime));
    }

    private function createConsumer()
    {
        $moduleName = ucfirst($this->exchange).'\\Module';
        if (!class_exists($moduleName)) {
            throw new InvalidArgumentException('Not found exchange: '.$this->exchange);
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
        $consumer->setExchangeOptions(['name' => $this->exchange, 'type' => 'topic']);
        $consumer->setRoutingKey($this->routingKey);
        $consumer->setQueueOptions(['name' => $this->exchange.'.'.$this->routingKey.'.'.$this->queue]);

        $consumer->setCallback(
            function ($msgBody): void {
                try {
                    $msg = \GuzzleHttp\json_decode($msgBody, true);
                    $this->consumerCallback($msg);
                } catch (\InvalidArgumentException $e) {
                    $this->output->writeln(sprintf('%s %d -1 "%s line %s %s"', DateTime::formatTime(), getmypid(), \get_class($e), __LINE__, $msgBody));
                }
            }
        );

        return $consumer;
    }
}
