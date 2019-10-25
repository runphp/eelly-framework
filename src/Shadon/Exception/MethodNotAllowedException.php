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
class MethodNotAllowedException extends RequestException
{
    private $statusCode = 405;

    private $errorCode = 405;

    private $hint = 'method not allowed';
}
