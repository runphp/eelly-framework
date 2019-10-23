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
 * Class NotFoundException.
 *
 * @author hehui<runphp@qq.com>
 */
class NotFoundException extends ClientException
{
    public function __construct($message = 'not found', $hint = 'not found', Throwable $previous = null)
    {
        parent::__construct($message, 404, $hint, $previous);
    }
}
