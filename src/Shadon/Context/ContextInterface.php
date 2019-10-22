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

use ReflectionMethod;

/**
 * Interface ContextInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface ContextInterface
{
    public function getModuleName(): string;

    public function setModuleName($moduleName): void;

    public function getController(): string;

    public function setController(string $controller): void;

    public function getAction(): string;

    public function setAction(string $action): void;

    public function getReflectionMethod(): ReflectionMethod;

    public function setReflectionMethod(ReflectionMethod $class): void;

    public function getParams(): array;

    public function setParams(array $params): void;

    public function push(callable $handler);

    public function next();
}
