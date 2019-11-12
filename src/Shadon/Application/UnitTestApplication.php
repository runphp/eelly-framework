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

use Composer\Autoload\ClassLoader;

class UnitTestApplication
{
    use RuntimeTrait;

    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     */
    public function context(string $rootPath, ClassLoader $classLoader)
    {
        return $this->registerService($classLoader, ...$this->initRuntime($rootPath));
    }
}
