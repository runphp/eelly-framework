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

namespace Shadon\Network;

use Shadon\Events\Listener\HttpServerListener;
use Shadon\Network\Traits\ServerTrait;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Lock;

/**
 * Class Server.
 */
class HttpServer extends SwooleHttpServer
{
    use ServerTrait;

    /**
     * event list.
     *
     * @var array
     */
    private const EVENTS = [
        'Start',
        'Shutdown',
        'WorkerStart',
        'WorkerStop',
        'Request',
        'Packet',
        'Close',
        'BufferFull',
        'BufferEmpty',
        'Task',
        'Finish',
        'PipeMessage',
        'WorkerError',
        'ManagerStart',
        'ManagerStop',
    ];

    /**
     * max module map count.
     */
    private const MAX_MODULE_MAP_COUNT = 50;

    /**
     * @var HttpServerListener
     */
    private $listner;

    /**
     * HttpServer constructor.
     *
     * @param string $host
     * @param int    $port
     */
    public function __construct(string $host, int $port)
    {
        parent::__construct($host, $port);
        $this->listner = new HttpServerListener();
        $this->lock = new Lock(SWOOLE_MUTEX);
        $this->moduleMap = $this->createModuleMap();
        foreach (self::EVENTS as $event) {
            $this->on($event, [$this->listner, 'on'.$event]);
        }
    }

    /**
     * register router.
     */
    public function registerRouter(): void
    {
        /* @var \Phalcon\Mvc\Router $router */
        $router = $this->di->getShared('router');
        // system
        $router->addPost('/_/:controller/:action', [
            'namespace'  => 'Shadon\\Controller',
            'controller' => 1,
            'action'     => 2,
        ]);
        // doc
        foreach ($this->di->getShared('config')->appBundles as $bundle) {
            $this->di->getShared($bundle)
                ->registerService()
                ->registerRouter();
        }
        // service api
        foreach ($this->di->getShared('config')->moduleList as $moduleName) {
            $namespace = ucfirst($moduleName).'\\Logic';
            $router->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace,
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ]);
        }
    }

    /**
     * Set process name.
     *
     * @param string $name
     */
    public function setProcessName(string $name): void
    {
        $processName = 'httpserver_'.$name;
        swoole_set_process_name($processName);
        $this->writeln($processName);
    }
}
