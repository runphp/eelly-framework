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

namespace Shadon\Codeception;

use Codeception\Configuration;
use Codeception\Exception\ModuleConfigException;
use Codeception\Exception\ModuleException;
use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\ActiveRecord;
use Codeception\Lib\Interfaces\PartedModule;
use Codeception\TestInterface;
use Dotenv\Dotenv;
use Phalcon\Config;
use Shadon\Application\ApplicationConst;
use Shadon\Di\ServiceDi;

/**
 * Class ShadonModule.
 *
 * @author hehui<runphp@dingtalk.com>
 */
class ShadonModule extends Framework implements ActiveRecord, PartedModule
{
    /**
     * HOOK: used after configuration is loaded.
     *
     * @throws ModuleConfigException
     */
    public function _initialize(): void
    {
        /* @var \Composer\Autoload\ClassLoader $loader */
        $loader = require 'vendor/autoload.php';

        $di = new ServiceDi();
        $di->setShared('loader', $loader);
        $dotenv = new Dotenv(getcwd(), '.env');
        $dotenv->load();
        $appEnv = getenv('APPLICATION_ENV');
        $appKey = getenv('APPLICATION_KEY');

        $loader = $di->getShared('loader');

        $arrayConfig = require 'var/config/config.'.$appEnv.'.php';
        \define('APP', [
            'env'      => $appEnv,
            'key'      => $appKey,
            'timezone' => $arrayConfig['timezone'],
            'appname'  => $arrayConfig['appName'],
        ]);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_CLI);
        $di->setShared('config', new Config($arrayConfig));
    }

    /**
     * HOOK: before scenario.
     *
     * @param TestInterface $test
     *
     * @throws ModuleException
     */
    public function _before(TestInterface $test): void
    {
    }

    /**
     * HOOK: after scenario.
     *
     * @param TestInterface $test
     */
    public function _after(TestInterface $test): void
    {
    }

    public function haveRecord($model, $attributes = []): void
    {
        // TODO: Implement haveRecord() method.
    }

    public function seeRecord($model, $attributes = []): void
    {
        // TODO: Implement seeRecord() method.
    }

    public function dontSeeRecord($model, $attributes = []): void
    {
        // TODO: Implement dontSeeRecord() method.
    }

    public function grabRecord($model, $attributes = []): void
    {
        // TODO: Implement grabRecord() method.
    }

    public function _parts(): void
    {
        // TODO: Implement _parts() method.
    }
}
