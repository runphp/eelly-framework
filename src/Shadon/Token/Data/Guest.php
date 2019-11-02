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

namespace Shadon\Token\Data;

use ArrayIterator;

/**
 * Class Guest.
 *
 * @author hehui<runphp@qq.com>
 */
class Guest extends ArrayIterator implements DataInterface
{
    public function __get($name)
    {
        return $this[$name];
    }

    public function __set($name, $value): void
    {
        $this[$name] = $value;
    }
}
