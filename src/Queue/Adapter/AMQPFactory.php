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

namespace Eelly\Queue\Adapter;

use Eelly\Queue\QueueFactoryInterface;
use Phalcon\Di\Injectable;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Thumper\ConnectionRegistry;
use Thumper\Consumer;
use Thumper\Producer;

/**
 * AMQP队列工厂实现.
 *
 * create Producer and Consumer
 *
 * @author hehui<hehui@eelly.net>
 */
class AMQPFactory extends Injectable implements QueueFactoryInterface
{
    /**
     * @var Producer[]
     */
    private $producers;

    /**
     * @var Consumer[]
     */
    private $consumers;

    /**
     * @var string
     */
    private $defaultProducer;

    /**
     * @var string
     */
    private $defaultConsumer;

    /**
     * @var array
     */
    private $connectionOptions;

    /**
     * constuct.
     *
     * $connectionOptions 示例
     * ```
     * [
     *     'default' => [.
     *         'host' => '172.18.107.245',
     *         'port' => '5672',
     *         'user' => 'guest',
     *         'password' => 'guest',
     *         'vhost' => '/',
     *      ],
     * ];
     * ```
     *
     * @param array $connectionOptions 连接信息
     * @param string $defaultProducer   默认生产�
     * @param string $defaultConsumer   默认消费�
     */
    public function __construct(array $connectionOptions, string $defaultProducer, string $defaultConsumer)
    {
        $this->connectionOptions = $connectionOptions;
        $this->defaultProducer = $defaultProducer;
        $this->defaultConsumer = $defaultConsumer;
    }

    public function afterServiceResolve(): void
    {
        $connectionOptions = $this->connectionOptions;
        $this->getDI()->setShared(ConnectionRegistry::class, function () use ($connectionOptions) {
            $connections = [];
            foreach ($connectionOptions as $key => $option) {
                $connections[$key] = new AMQPLazyConnection($option['host'], $option['port'], $option['user'], $option['password']);
            }
            $registry = new ConnectionRegistry($connections, 'default');

            return $registry;
        });
    }

    /**
     * create producer.
     *
     * @param string $name
     *
     * @return Producer
     */
    public function createProducer(string $name = null)
    {
        if (null === $name) {
            $name = $this->defaultProducer;
        }

        if (!isset($this->producers[$name])) {
            /**
             * @var \Thumper\ConnectionRegistry
             */
            $connectionRegistry = $this->getDI()->get(ConnectionRegistry::class);

            $connection = $connectionRegistry->getConnection($name);
            $this->producers[$name] = new Producer($connection);
        }

        return $this->producers[$name];
    }

    /**
     * create consumer.
     *
     * @param string $name
     *
     * @return Consumer
     */
    public function createConsumer(string $name = null)
    {
        if (null === $name) {
            $name = $this->defaultConsumer;
        }

        if (!isset($this->consumers[$name])) {
            /**
             * @var \Thumper\ConnectionRegistry
             */
            $connectionRegistry = $this->getDI()->get(ConnectionRegistry::class);

            $connection = $connectionRegistry->getConnection($name);
            $this->consumers[$name] = new Consumer($connection);
        }

        return $this->consumers[$name];
    }
}
