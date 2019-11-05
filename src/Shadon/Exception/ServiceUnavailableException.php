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
 * Class ServiceUnavailableException.
 *
 * @author hehui<runphp@qq.com>
 */
class ServiceUnavailableException extends AbstractException
{
    protected $code = E_CORE_ERROR;

    protected $statusCode = 503;

    protected $errorCode = 503;

    protected $message = 'service unavailable';

    protected $hint = '无法提供服务';
}
