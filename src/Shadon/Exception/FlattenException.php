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

use Symfony\Component\Debug\Exception\FlattenException as SymfonyException;

class FlattenException extends SymfonyException
{
    /**
     * @var int
     */
    private $errorCode = 500;

    /**
     * @var string
     */
    private $hint = '服务器异常';

    public static function createFromThrowable(\Throwable $exception, ?int $statusCode = null, array $headers = []): SymfonyException
    {
        /* @var $return FlattenException */
        $return = parent::createFromThrowable($exception, $statusCode, $headers);
        if ($exception instanceof Exception) {
            $return->setStatusCode($exception->getStatusCode());
            $return->setErrorCode($exception->getErrorCode());
            $return->setHint($exception->getHint());
        }

        return $return;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode(int $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }

    /**
     * @param string $hint
     */
    public function setHint(string $hint): void
    {
        $this->hint = $hint;
    }
}
