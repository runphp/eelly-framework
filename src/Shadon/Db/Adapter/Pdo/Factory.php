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

namespace Shadon\Db\Adapter\Pdo;

use Phalcon\Factory as BaseFactory;

class Factory extends BaseFactory
{
    /**
     * {@inheritdoc}
     */
    public static function load($config)
    {
        return self::loadClass('Shadon\\Db\\Adapter\\Pdo', $config);
    }
}
