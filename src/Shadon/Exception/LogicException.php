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

/**
 * Class LogicException.
 *
 * @author hehui<runphp@qq.com>
 */
class LogicException extends AbstractException
{
    protected $statusCode = 200;

    protected $errorCode = 10000;

    protected $hint = '逻辑异常';
}
