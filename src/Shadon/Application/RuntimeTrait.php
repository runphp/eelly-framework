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

use Shadon\Exception\ExceptionHandler;
use Shadon\Exception\ServerException;
use Symfony\Component\Debug\ErrorHandler;

/**
 * Trait RuntimeTrait.
 *
 * hehui<runphp@qq.com>
 */
trait RuntimeTrait
{
    /**
     * Initialize runtime.
     *
     * @param string $rootPath
     *
     * @throws \Exception
     *
     * @return array
     */
    private function initRuntime(string $rootPath): array
    {
        $errorhandler = ErrorHandler::register();
        $this->initEnvironment($rootPath);
        $exceptionHandler = ExceptionHandler::register('develop' == APP['env']);
        if (\in_array(false, APP)) {
            throw new ServerException('error runtime, check `.env`');
        }

        return [$errorhandler, $exceptionHandler];
    }

    /**
     * Initiali app env.
     *
     * @param string $rootPath
     *
     * @throws \Exception
     */
    private function initEnvironment(string $rootPath): void
    {
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APP_KEY=/m',
                    'APP_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = \Dotenv\Dotenv::create($rootPath);
        $dotenv->load();
        \define('APP', [
            'env'        => getenv('APP_ENV'),
            'key'        => getenv('APP_KEY'),
            'namespace'  => getenv('NS'),
            'rootPath'   => $rootPath,
            'serverName' => 'Shadon',
            'version'    => '2.0',
        ]);
    }
}
