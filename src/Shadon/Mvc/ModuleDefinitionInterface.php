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

namespace Shadon\Mvc;

/**
 * Interface ModuleDefinitionInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface ModuleDefinitionInterface
{
    /**
     * Register module service.
     *
     * @param callable $moduleConfig
     */
    public function registerService(callable $moduleConfig): void;

    /**
     * Register module event.
     */
    public function registerEvent(): void;
}
