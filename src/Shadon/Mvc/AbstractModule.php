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

use Shadon\Context\ContextInterface;

/**
 * @author hehui<runphp@qq.com>
 */
abstract class AbstractModule implements ModuleDefinitionInterface
{
    /**
     * @Inject
     *
     * @var ContextInterface
     */
    private $context;

    /**
     * Add handler.
     *
     * @param callable ...$handlers
     */
    public function addHandler(callable ...$handlers): void
    {
        foreach ($handlers as $handler) {
            $this->context->push($handler);
        }
    }
}
