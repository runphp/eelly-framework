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

require __DIR__.'/path.php';

/**
 * @return bool
 */
function isCli()
{
    return \PHP_SAPI === 'cli';
}
