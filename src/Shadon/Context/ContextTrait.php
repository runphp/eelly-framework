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

use DI\Container;
use SplStack;

/**
 * Trait ContextTrait.
 *
 * @author hehui<runphp@qq.com>
 */
trait ContextTrait
{
    /**
     * @var Container
     */
    private $di;

    /**
     * map.
     *
     * @var array
     */
    private $entries = [];

    /**
     * @var SplStack
     */
    private $handlerStack;

    public function __construct()
    {
        $this->handlerStack = new SplStack();
    }

    public function push(callable $handler): void
    {
        $this->handlerStack->push($handler);
    }

    public function next()
    {
        $handler = $this->handlerStack->shift();

        return $handler($this);
    }

    public function get($name)
    {
        if (isset($this->entries[$name])) {
            return $this->entries[$name];
        } else {
            return $this->di->get($name);
        }
    }

    public function set(string $name, $value): void
    {
        $this->entries[$name] = $value;
    }

    public function has(string $name): bool
    {
        return isset($this->entries[$name]) || $this->di->has($name);
    }

    public function injectOn($object)
    {
        return $this->di->injectOn($object);
    }

    public function moduleConfig($name): array
    {
        return require sprintf('var/config/%s/%s/%s.php', APP['env'], $this->get('module'), $name);
    }
}
