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

namespace Shadon\Mvc\Model;

use Phalcon\Mvc\Model\Manager as ModelManager;
use Shadon\Mvc\Model\Query\Builder;

class Manager extends ModelManager
{
    /**
     * {@inheritdoc}
     *
     * @see ModelManager::createBuilder()
     */
    public function createBuilder($params = null)
    {
        return $this->getDI()->get(
            Builder::class,
            [
                $params,
                $this->getDI(),
            ]
            );
    }
}
