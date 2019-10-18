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

namespace Shadon\Queue;

/**
 * Queue factory interface.
 *
 * TODO 生产者接口和消费者接口
 *
 * @author hehui<hehui@eelly.net>
 */
interface QueueFactoryInterface
{
    /**
     * @param string $name
     */
    public function createProducer(string $name = null);

    /**
     * @param string $name
     */
    public function createConsumer(string $name = null);
}
