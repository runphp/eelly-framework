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

use Exception as PhpExcepton;
use Throwable;

/**
 * Class Exception.
 *
 * @author hehui<runphp@qq.com>
 */
class Exception extends PhpExcepton
{
    /**
     * @var int
     */
    protected $statusCode = 500;

    /**
     * @var int
     */
    protected $errorCode = 500;

    /**
     * @var string
     */
    protected $hint = '服务器异常';

    public function __construct(string $hint = '', $message = '', $code = 0, Throwable $previous = null)
    {
        $this->hint = '' === $hint ? $this->hint : $hint;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }
}
