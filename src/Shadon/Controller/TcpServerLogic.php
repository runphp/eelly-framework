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

namespace Shadon\Controller;

use Shadon\Mvc\Controller;

/**
 * Class TcpServerLogic.
 *
 * @property \Shadon\Network\HttpServer $server
 */
class TcpServerLogic extends Controller
{
    public function register(): array
    {
        $module = $this->request->getPost('module');
        $ip = $this->request->getPost('ip', null, $this->request->getClientAddress());
        $port = (int) $this->request->getPost('port');
        $this->server->registerModule($module, $ip, $port);

        return $this->server->getModuleMap();
    }
}
