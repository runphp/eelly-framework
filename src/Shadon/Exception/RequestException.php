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
 * Class RequestException.
 *
 * @author hehui<runphp@qq.com>
 */
class RequestException extends ClientException
{
    public function __construct($message = 'bad request', $hint = 'bad request', Throwable $previous = null)
    {
        parent::__construct($message, 400, $hint, $previous);
    }
}
