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
abstract class AbstractException extends PhpExcepton
{
    /**
     * http status code.
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * unique error code.
     *
     * @var int
     */
    protected $errorCode = 500;

    /**
     * hint.
     *
     * @var string
     */
    protected $hint = '服务器异常';

    /**
     * Exception constructor.
     *
     * @param string         $message  error info
     * @param string         $hint     hint info
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', string $hint = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->hint = '' === $hint ? $this->hint : $hint;
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
