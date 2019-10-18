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

namespace Shadon\Queue\Adapter;

class Producer extends \Thumper\Producer
{
    private const PREFIX = 'eelly_api.';

    /**
     * @param array $options
     */
    public function setExchangeOptions(array $options): void
    {
        if (isset($options['name'])) {
            $options['name'] = self::PREFIX.$options['name'];
        }
        parent::setExchangeOptions($options);
    }

    /**
     * @return \PhpAmqpLib\Connection\AbstractConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }

    /**
     * 发布作业.
     *
     * @param string $moduleName 模块名
     * @param string $class      类名
     * @param string $method     方法名
     * @param array  $params     参数
     * @param string $routingKey 路由
     */
    public function publishJob(string $moduleName, string $class, string $method, array $params, string $routingKey = 'default_routing_key'): void
    {
        $this->setExchangeOptions(['name' => $moduleName, 'type' => 'topic']);
        $messageBody = [
            'class'   => $class,
            'method'  => $method,
            'params'  => $params,
            'time'    => microtime(true),
        ];
        parent::publish(\GuzzleHttp\json_encode($messageBody), $routingKey);
        $connection = $this->channel->getConnection();
        if ($connection->isConnected()) {
            try {
                $connection->close();
            } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
            }
        }
    }
}
