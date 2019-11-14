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

namespace Shadon\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Class ConsumeCommandFactory.
 *
 * @author hehui<runphp@qq.com>
 */
class ConsumeCommandFactory
{
    public static function create($module): Command
    {
        return  new ConsumeCommand($module);
    }
}
