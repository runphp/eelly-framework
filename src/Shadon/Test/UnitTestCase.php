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

use Phalcon\Di;
use Phalcon\DiInterface;
use PHPUnit\Framework\TestCase;
use Shadon\Di\InjectionAwareInterface;

/**
 * Class UnitTestCase.
 */
class UnitTestCase extends TestCase implements InjectionAwareInterface
{
    /**
     * @var DiInterface
     */
    protected $di;

    public static function setUpBeforeClass(): void
    {
        $di = Di::getDefault();
        $loader = $di->get('loader');
        list($moduleName) = explode('\\', static::class);
        $loader->addPsr4($moduleName.'\\', 'src/'.$moduleName);
        $loader->register();

        $module = $di->getShared('\\'.$moduleName.'\\Module');
        $module->registerAutoloaders($di);
        $module->registerServices($di);
    }

    /**
     * Sets the Dependency Injector.
     *
     * @see    Injectable::setDI
     *
     * @param DiInterface $di
     *
     * @return $this
     */
    public function setDI(DiInterface $di)
    {
        $this->di = $di;

        return $this;
    }

    /**
     * Returns the internal Dependency Injector.
     *
     * @see    Injectable::getDI
     *
     * @return DiInterface
     */
    public function getDI()
    {
        if (!$this->di instanceof DiInterface) {
            return Di::getDefault();
        }

        return $this->di;
    }
}
