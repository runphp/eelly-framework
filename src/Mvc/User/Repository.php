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

namespace Eelly\Mvc\User;

use Eelly\Di\Injectable;

class Repository extends Injectable
{
    /**
     * 模型实例.
     *
     * @var array
     */
    private $modelInstance = [];

    /**
     * 获取模型实例.
     *
     * @param string $modelName 模型名称
     *
     * @return Model
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since 2017年10月9日
     */
    protected function getModel(string $modelName)
    {
        return $this->modelInstance[$modelName] ?? ($this->modelInstance[$modelName] = new $modelName());
    }
}
