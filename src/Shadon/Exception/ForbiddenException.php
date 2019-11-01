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
 * Class ForbiddenException.
 *
 * @author hehui<runphp@qq.com>
 */
class ForbiddenException extends RequestException
{
    protected $code = E_USER_NOTICE;

    protected $statusCode = 403;

    protected $errorCode = 403;

    protected $message = 'forbidden';

    protected $hint = '无权限';
}
