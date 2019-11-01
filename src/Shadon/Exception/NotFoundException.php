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
 * Class NotFoundException.
 *
 * @author hehui<runphp@qq.com>
 */
class NotFoundException extends AbstractException
{
    protected $code = E_USER_NOTICE;

    protected $statusCode = 404;

    protected $errorCode = 404;

    protected $message = 'not found';

    protected $hint = '未找到';
}
