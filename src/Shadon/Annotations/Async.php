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
 *     @Attribute("routingKey", type="string")
 * })
 *
 * @author hehui<runphp@qq.com>
 */
class Async
{
    /**
     * @var string
     */
    private $routingKey = 'default';

    public function __construct(array $values)
    {
        $this->routingKey = $values['routingKey'] ?? $this->routingKey;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
