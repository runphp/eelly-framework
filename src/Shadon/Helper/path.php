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

namespace Shadon\Helper;

use function realpath as real_path;

/**
 * Get real path.
 *
 * add APP['rootPath']
 *
 * @param string $path
 *
 * @return string
 *
 * @author hehui<runphp@qq.com>
 */
function realpath(string $path)
{
    $realpath = real_path($path);
    if (false === $path) {
        $realpath = APP['rootPath'].'/'.$path;
    }

    return $realpath;
}
