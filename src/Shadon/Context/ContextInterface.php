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

/**
 * Interface ContextInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface ContextInterface
{
    public function push(callable $handler);

    public function next();

    public function get($name);

    public function set(string $name, $value): void;

    public function has(string $name): bool;

    public function injectOn($instance);

    public function token(?string $token = null, ?array $data = null): array;
}
