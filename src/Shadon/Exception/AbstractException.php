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

use Symfony\Component\Debug\Exception\FatalErrorException;

/**
 * Class Exception.
 *
 * @author hehui<runphp@qq.com>
 */
abstract class AbstractException extends FatalErrorException
{
    /**
     * @var int
     */
    protected $code = E_CORE_ERROR;

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
     * @var string
     */
    protected $message = 'server error';

    /**
     * hint.
     *
     * @var string
     */
    protected $hint = '服务器异常';

    /**
     * AbstractException constructor.
     *
     * @param string $message
     * @param string $hint
     * @param int    $code
     * @param int    $severity
     */
    public function __construct(string $message = '', string $hint = '')
    {
        parent::__construct('' === $message ? $this->message : $message, $this->code, $this->code, $this->getFile(), $this->getLine());
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
