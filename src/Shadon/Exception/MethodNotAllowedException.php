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

use Throwable;

/**
 * Class MethodNotAllowedException.
 *
 * @author hehui<runphp@qq.com>
 */
class MethodNotAllowedException extends ClientException
{
    public function __construct($message = 'method not allowed', $tips = 'method not allowed', Throwable $previous = null)
    {
        parent::__construct($message, 405, $tips, $previous);
    }
}
