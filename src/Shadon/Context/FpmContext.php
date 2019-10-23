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
use SplStack;

/**
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    use ContextTrait;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;
    /**
     * @var string
     */
    private $moduleName;
    /**
     * @var ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @var array
     */
    private $params;

    /**
     * @var int
     */
    private $tpl;

    /**
     * @var string
     */
    private $requestId;

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

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getReflectionMethod(): ReflectionMethod
    {
        return $this->reflectionMethod;
    }

    public function setReflectionMethod(ReflectionMethod $reflectionMethod): void
    {
        $this->reflectionMethod = $reflectionMethod;
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
     * @return int
     */
    public function getTpl(): int
    {
        return $this->tpl;
    }

    /**
     * @param int $tpl
     */
    public function setTpl(int $tpl): void
    {
        $this->tpl = $tpl;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     */
    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
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
}
