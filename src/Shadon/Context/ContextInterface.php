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

namespace Shadon\Context;

use Psr\Container\ContainerInterface;

/**
 * Interface ContextInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface ContextInterface extends ContainerInterface
{
    public function push(callable $handler);

    public function next();

    public function set(string $name, $value): void;

    public function injectOn($instance);

    public function moduleConfig($name): array;
}
