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

namespace Shadon\Annotations;

use Doctrine\Common\Annotations\Annotation\Attribute;

/**
 * Async annotation.
 *
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("routingKey", type="string"),
 *     @Attribute("timeToLive", type="int"),
 *     @Attribute("delay", type="int")
 * })
 *
 * @author hehui<runphp@qq.com>
 */
class Async
{
    /**
     * 路由key.
     *
     * @var string
     */
    private $routingKey = 'default';

    /**
     * 有效期.
     *
     * @var int
     */
    private $timeToLive;

    /**
     * 延时.
     *
     * @var int
     */
    private $delay = 0;

    public function __construct(array $values)
    {
        $this->routingKey = $values['routingKey'] ?? $this->routingKey;
        $this->timeToLive = $values['timeToLive'] ?? $this->timeToLive;
        $this->delay = $values['delay'] ?? $this->delay;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return int|null
     */
    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }
}
