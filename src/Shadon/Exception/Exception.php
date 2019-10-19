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
 * Class Exception.
 *
 * @author hehui<runphp@qq.com>
 */
class Exception extends \Exception
{
    /**
     * tips.
     *
     * @var string
     */
    protected $tips;

    public function __construct($message = 'uncatched exception', $code = 500, $tips = '服务器异常', Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->tips = $tips;
    }
}
