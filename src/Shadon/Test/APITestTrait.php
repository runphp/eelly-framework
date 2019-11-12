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

namespace Shadon\Test;

use Composer\Autoload\ClassLoader;
use Shadon\Application\UnitTestApplication;
use Shadon\Context\ContextInterface;

/**
 * Trait APITestTrait.
 *
 * @author hehui<runphp@qq.com>
 */
trait APITestTrait
{
    /**
     * @var ContextInterface
     */
    private static $context;

    public static function setUpBeforeClass(): void
    {
        if (!\defined('ROOT_PATH')) {
            \define('ROOT_PATH', realpath(\dirname(__DIR__, 6)));
            chdir(ROOT_PATH);
        }
        if (null === self::$context) {
            $classLoader = require ROOT_PATH.'/vendor/autoload.php';
            self::$context = (new UnitTestApplication())->context(ROOT_PATH, $classLoader);
        }
        preg_match('/.+\\\Module\\\(.+)\\\API\\\.+/', static::class, $matches);
        $module = lcfirst($matches[1]);
        self::$context->set('module', $module);
        $moduleNamespace = APP['namespace'].'\\Module\\'.ucfirst($module);
        self::$context->get(ClassLoader::class)->addPsr4($moduleNamespace.'\\', 'src/Module/'.ucfirst($module.'/'));
        // initial moudle instance
        $moduleInstance = self::$context->get($moduleNamespace.'\\Module');
        $moduleInstance->init();
    }

    protected function setUp(): void
    {
        self::$context->injectOn($this);
    }
}
