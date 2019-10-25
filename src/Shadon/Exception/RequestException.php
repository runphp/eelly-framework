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
 * Class RequestException.
 *
 * @author hehui<runphp@qq.com>
 */
class RequestException extends Exception
{
    protected $statusCode = 400;

    protected $errorCode = 400;

    protected $hint = 'bad request';
}
