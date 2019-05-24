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

use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends \Thumper\Consumer
{
    private const PREFIX = 'eelly_api.';

    /**
     * Target number of messages to consume.
     *
     * @var int
     */
    private $target;

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
     * @param array $options
     */
    public function setQueueOptions(array $options): void
    {
        if (isset($options['name'])) {
            $options['name'] = self::PREFIX.$options['name'];
        }
        parent::setQueueOptions($options);
    }

    /**
     * @param int $numOfMessages
     */
    public function consume($numOfMessages): void
    {
        $this->target = $numOfMessages;

        $this->setUpConsumer();

        while (\count($this->channel->callbacks)) {
            $this->channel->wait(null, false, 30);
        }
    }

    /**
     * @return \PhpAmqpLib\Connection\AbstractConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }

    public function getQueueOptions()
    {
        return $this->queueOptions;
    }

    /**
     * @param AMQPMessage $message
     */
    protected function maybeStopConsumer(AMQPMessage $message): void
    {
        if ($this->consumed == $this->target) {
            $message->delivery_info['channel']
                ->basic_cancel($message->delivery_info['consumer_tag']);
            $this->consumed = 0;
        }
    }

    /**
     * @return string
     */
    protected function getConsumerTag()
    {
        static $time;
        if (null === $time) {
            $time = date('mdHis');
        }

        return 'PP_'.$time.'_'.getmypid();
    }
}
