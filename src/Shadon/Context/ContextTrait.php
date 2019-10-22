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

trait ContextTrait
{
    public function moduleConfig($name)
    {
        return require sprintf('var/config/%s/%s/%s.php', APP['env'], $this->moduleName, $name);
    }
}
