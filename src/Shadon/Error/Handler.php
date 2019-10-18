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

namespace Shadon\Error;

use ErrorException;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Phalcon\Di\Injectable;
use Psr\Log\LogLevel;
use Shadon\Application\ApplicationConst;
use Throwable;

/**
 * @author hehui<hehui@eelly.net>
 */
class Handler extends Injectable
{
    /**
     * fatal errors.
     *
     * @var array
     */
    private const FATAL_ERRORS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ];

    /**
     * reserved memory.
     *
     * @var string
     */
    private $reservedMemory;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * Registers itself as error and exception handler.
     *
     * @link https://github.com/php/php-src/blob/master/php.ini-production#L458
     */
    public function register(): self
    {
        // dev本地，local 待上线，prod 线上，test 测试
        ini_set('display_errors', '0');
        switch (APP['env']) {
            case ApplicationConst::ENV_TEST:
            case ApplicationConst::ENV_DEVELOPMENT:
                error_reporting(E_ALL);
                break;
            case ApplicationConst::ENV_PRODUCTION:
            case ApplicationConst::ENV_STAGING:
                error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
                break;
        }
        $handler = new static();
        $handler->registerErrorHandler();
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();

        return $handler;
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $di = $this->getDI();
            $this->logger = $di->getShared('errorLogger');
            $di->has('errorViewHandler') && $this->logger->pushHandler($di->getShared('errorViewHandler'));
        }

        return $this->logger;
    }

    public function registerErrorHandler(): void
    {
        set_error_handler([$this, 'handleError'], -1);
    }

    public function registerExceptionHandler(): void
    {
        set_exception_handler([$this, 'handleException']);
    }

    public function registerFatalHandler($reservedMemorySize = 20): void
    {
        register_shutdown_function([$this, 'handleFatalError'], getcwd());
        $this->reservedMemory = str_repeat(' ', 1024 * $reservedMemorySize);
    }

    public function handleError($code, $message, $file = '', $line = 0, $context = []): void
    {
        if (!($code & error_reporting())) {
            return;
        }

        throw new ErrorException($message, 0, $code, $file, $line);
    }

    public function handleException(Throwable $e): void
    {
        $errorLevelMap = $this->defaultErrorLevelMap();
        $level = $errorLevelMap[$e->getCode()] ?? LogLevel::ERROR;
        $message = $e->getMessage();
        $encode = mb_detect_encoding($message, 'UTF-8,GBK');
        if ('UTF-8' != $encode) {
            $message = mb_convert_encoding($message, 'UTF-8', $encode);
        }
        $this->getLogger()->log($level, 'Uncaught Exception: '.\get_class($e), [
            'code'          => $e->getCode(),
            'message'       => $message,
            'class'         => \get_class($e),
            'file'          => $e->getFile(),
            'line'          => $e->getLine(),
            'traceAsString' => $e->getTraceAsString(),
        ]);
    }

    public function handleFatalError($currPath): void
    {
        chdir($currPath);
        $this->reservedMemory = null;
        $lastError = error_get_last();
        if ($lastError && \in_array($lastError['type'], self::FATAL_ERRORS, true)) {
            $logger = $this->getLogger();
            $logger->log(
                LogLevel::ALERT,
                'Fatal Error ('.self::codeToString($lastError['type']).'): '.$lastError['message'],
                [
                    'code'    => $lastError['type'],
                    'message' => $lastError['message'],
                    'class'   => 'ErrorException',
                    'file'    => $lastError['file'],
                    'line'    => $lastError['line'],
                ]
            );

            if ($logger instanceof Logger) {
                foreach ($logger->getHandlers() as $handler) {
                    if ($handler instanceof AbstractHandler) {
                        $handler->close();
                    }
                }
            }
        }
    }

    protected function defaultErrorLevelMap()
    {
        return [
            E_ERROR             => LogLevel::CRITICAL,
            E_WARNING           => LogLevel::WARNING,
            E_PARSE             => LogLevel::ALERT,
            E_NOTICE            => LogLevel::NOTICE,
            E_CORE_ERROR        => LogLevel::CRITICAL,
            E_CORE_WARNING      => LogLevel::WARNING,
            E_COMPILE_ERROR     => LogLevel::ALERT,
            E_COMPILE_WARNING   => LogLevel::WARNING,
            E_USER_ERROR        => LogLevel::ERROR,
            E_USER_WARNING      => LogLevel::WARNING,
            E_USER_NOTICE       => LogLevel::NOTICE,
            E_STRICT            => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED        => LogLevel::NOTICE,
            E_USER_DEPRECATED   => LogLevel::NOTICE,
        ];
    }

    private static function codeToString($code)
    {
        switch ($code) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return 'Unknown PHP error';
    }
}
