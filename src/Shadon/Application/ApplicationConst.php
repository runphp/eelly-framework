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

namespace Shadon\Application;

/**
 * Application const variable and static variable.
 *
 * @author hehui<hehui@eelly.net>
 */
final class ApplicationConst
{
    /**
     * prod 生产环境.
     *
     * @var string
     */
    public const ENV_PRODUCTION = 'prod';

    /**
     * staging 预发布环境.
     *
     * @var string
     */
    public const ENV_STAGING = 'staging';

    /**
     * test 测试环境.
     *
     * @var string
     */
    public const ENV_TEST = 'test';

    /**
     * develop 本地开发环境.
     *
     * @var string
     */
    public const ENV_DEVELOPMENT = 'develop';

    /**
     * app name.
     *
     * @var string
     */
    public const APP_NAME = 'EELLY';

    /**
     * app version.
     *
     * @var string
     */
    public const APP_VERSION = '0.2.4';

    /**
     * fpm runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_FPM = 1;

    /**
     * cli runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_CLI = 2;

    /**
     * swoole runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_SWOOLE = 4;

    /**
     * service runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_SERVICE = 8;

    /**
     * app runtime environment.
     *
     * @var int
     */
    public static $runtimeEnv = 0;

    /**
     * app name.
     *
     * @var string
     */
    public static $appName = 'App';

    /**
     * oauth info.
     *
     * @var array
     */
    public static $oauth;

    /**
     * Request Action.
     *
     * @var string
     */
    public static $requestAction = '';

    /**
     * Append runtime environment.
     *
     * @param int $runtimeEnv
     */
    public static function appendRuntimeEnv(int $runtimeEnv): void
    {
        self::$runtimeEnv |= $runtimeEnv;
    }

    /**
     * Has runtime environment.
     *
     * @param int $runtimeEnv
     *
     * @return bool
     */
    public static function hasRuntimeEnv(int $runtimeEnv): bool
    {
        return $runtimeEnv == (self::$runtimeEnv & $runtimeEnv);
    }

    /**
     * @return string
     */
    public static function getRequestAction(): string
    {
        return self::$requestAction;
    }

    /**
     * @param string $requestAction
     */
    public static function setRequestAction(string $requestAction): void
    {
        self::$requestAction = $requestAction;
    }
}
