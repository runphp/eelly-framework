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

namespace Shadon\Session;

use Phalcon\Session\AdapterInterface;
use Phalcon\Session\Factory as SessionFactory;

class Factory extends SessionFactory
{
    /**
     * @param \Phalcon\Config|array config $config
     */
    public static function load($config): AdapterInterface
    {
        if (class_exists('Shadon\\Session\\Adapter\\'.$config['adapter'])) {
            $adapter = self::loadClass('Shadon\\Session\\Adapter', $config);
        } else {
            $adapter = parent::load($config);
        }

        return $adapter;
    }
}
