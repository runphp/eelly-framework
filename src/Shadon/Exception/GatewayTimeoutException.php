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
 * Class GatewayTimeoutException.
 *
 * @author hehui<runphp@qq.com>
 */
class GatewayTimeoutException extends AbstractException
{
    protected $code = E_CORE_ERROR;

    protected $statusCode = 504;

    protected $errorCode = 504;

    protected $message = 'gateway timeout';

    protected $hint = '网关超时';
}
