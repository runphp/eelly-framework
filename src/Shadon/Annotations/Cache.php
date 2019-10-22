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
 * Cache annotation.
 *
 * @Annotation
 * @Target({"METHOD","PROPERTY"})
 * @Attributes({
 *     @Attribute("lifetime", type="int")
 * })
 *
 * @author hehui<runphp@qq.com>
 */
class Cache
{
    /**
     * @var int
     */
    private $lifetime = 3600;

    public function __construct(array $values)
    {
        $this->lifetime = $values['lifetime'] ?? $this->lifetime;
    }

    /**
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }
}
