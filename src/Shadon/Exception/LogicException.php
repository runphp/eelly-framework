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

namespace Shadon\Exception;

use Throwable;

/**
 * Class LogicException.
 *
 * @author hehui<runphp@qq.com>
 */
class LogicException extends Exception
{
    public function __construct($message = '业务逻辑异常', $code = 4001, $tips = '业务逻辑异常', Throwable $previous = null)
    {
        parent::__construct($message, $code, $tips, $previous);
    }
}
