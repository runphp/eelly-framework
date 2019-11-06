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
 * Class BadGatewayException.
 *
 * @author hehui<runphp@qq.com>
 */
class BadGatewayException extends AbstractException
{
    protected $code = E_CORE_ERROR;

    protected $statusCode = 502;

    protected $errorCode = 502;

    protected $message = 'bad gateway';

    protected $hint = '错误网关';
}
