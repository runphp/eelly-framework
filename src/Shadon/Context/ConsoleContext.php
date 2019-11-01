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

namespace Shadon\Context;

use Shadon\Exception\UnsupportedException;

/**
 * Class ConsoleContext.
 *
 * @author hehui<runphp@qq.com>
 */
class ConsoleContext implements ContextInterface
{
    use ContextTrait;

    public function token(?string $token = null, ?array $data = null): array
    {
        throw new UnsupportedException('命令行不支持token机制');
    }
}
