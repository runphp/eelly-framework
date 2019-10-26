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
 * Class MethodNotAllowedException.
 *
 * @author hehui<runphp@qq.com>
 */
class MethodNotAllowedException extends AbstractException
{
    protected $code = E_USER_NOTICE;

    protected $statusCode = 405;

    protected $errorCode = 405;

    protected $hint = 'method not allowed';
}
