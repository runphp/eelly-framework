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

class TooManyRequestsException extends RequestException
{
    protected $code = E_USER_NOTICE;

    protected $statusCode = 429;

    protected $errorCode = 429;

    protected $message = 'too many requests';

    protected $hint = '请求太频繁';
}