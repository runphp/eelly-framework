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
 * Class UnauthorizedException.
 *
 * @author hehui<runphp@qq.com>
 */
class UnauthorizedException extends RequestException
{
    protected $code = E_USER_NOTICE;

    protected $statusCode = 401;

    protected $errorCode = 401;

    protected $message = 'unauthorized';

    protected $hint = '未授权';
}
