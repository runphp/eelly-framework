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

use DI\Annotation\Inject;
use SplStack;

/**
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var array
     */
    private $params;

    /**
     * @Inject
     *
     * @var \DI\Container
     */
    private $di;

    /**
     * @var SplStack
     */
    private $handlerStack;

    public function __construct()
    {
        $this->handlerStack = new SplStack();
    }

    /**
     * @return mixed
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @param mixed $moduleName
     */
    public function setModuleName($moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return \Di\Container
     */
    public function getDi(): \Di\Container
    {
        return $this->di;
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

    public function moduleConfig($name)
    {
        return require sprintf('var/config/%s/%s/%s.php', APP['env'], $this->moduleName, $name);
    }
}
