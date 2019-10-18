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

namespace Shadon\Di;

use Phalcon\Config;

class TcpServerDi extends FactoryDefault
{
    /**
     * TcpServerDi constructor.
     *
     * @param string $env    环境(dev|local|prod)
     * @param string $module
     */
    public function __construct(string $env, string $module)
    {
        parent::__construct();
        $this->set('config', new Config(require 'var/config/config.php'), true);
        $this->set('moduleConfig', require 'var/config/'.$env.'/'.$module.'.php', true);
    }
}
