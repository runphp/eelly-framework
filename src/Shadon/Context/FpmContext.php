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
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    private $moduleName;

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

    public function moduleConfig($name)
    {
        return require sprintf('var/config/%s/%s/%s.php', APP['env'], $this->moduleName, $name);
    }
}
