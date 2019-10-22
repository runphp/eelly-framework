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
    public function getModuleName(): string;

    public function setModuleName($moduleName): void;

    public function moduleConfig($name);
}
