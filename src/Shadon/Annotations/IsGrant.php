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

use Shadon\Token\Data\Guest;

/**
 * Class IsGrant.
 *
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("role", type="string")
 * })
 *
 * @author hehui<runphp@qq.com>
 */
class IsGrant
{
    /**
     * @var string
     */
    private $role = Guest::class;

    public function __construct(array $values)
    {
        $this->role = $values['role'] ?? $this->role;
    }

    /**
     * @return int
     */
    public function getRole(): string
    {
        return $this->role;
    }
}
