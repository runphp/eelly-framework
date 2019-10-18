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

use Shadon\Events\Listener\TcpServerListner;
use Shadon\Network\Traits\ServerTrait;
use Swoole\Lock;
use Swoole\Server;

/**
 * Class TcpServer.
 */
class TcpServer extends Server
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
        'Connect',
        'Receive',
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
     * @var TcpServerListner
     */
    private $listner;

    /**
     * @var string
     */
    private $moduleName;

    /**
     * TcpServer constructor.
     *
     * @param string $host
     * @param int    $port
     * @param int    $mode
     * @param int    $sockType
     */
    public function __construct(string $host, int $port = 0, int $mode = SWOOLE_PROCESS, int $sockType = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
        $this->listner = new TcpServerListner();
        $this->lock = new Lock(SWOOLE_MUTEX);
        $this->moduleMap = $this->createModuleMap();
        foreach (self::EVENTS as $event) {
            $this->on($event, [$this->listner, 'on'.$event]);
        }
    }

    /**
     * Set process name.
     *
     * @param string $name
     */
    public function setProcessName(string $name): void
    {
        $processName = $this->moduleName.'_'.$name;
        swoole_set_process_name($processName);
        $this->writeln($processName);
    }

    /**
     * Initialize module.
     */
    public function initializeModule(): void
    {
        $module = ucfirst($this->moduleName).'\\Module';
        /* @var \Shadon\Mvc\AbstractModule $moduleInstance */
        $moduleInstance = $this->di->getShared($module);
        $moduleInstance->registerAutoloaders($this->di);
        $moduleInstance->registerServices($this->di);
    }

    /**
     * Register router.
     */
    public function registerRouter(): void
    {
        /* @var \Phalcon\Mvc\Router $router */
        $router = $this->di->getShared('router');
        $moduleName = $this->moduleName;
        $namespace = ucfirst($moduleName).'\\Logic';
        $router->add('/'.$moduleName.'/:controller/:action', [
            'namespace'  => $namespace,
            'module'     => $moduleName,
            'controller' => 1,
            'action'     => 2,
        ]);
    }

    /**
     * @param int    $fd
     * @param string $data
     * @param int    $fromId
     *
     * @return bool
     */
    public function send($fd, $data, $fromId = 0): bool
    {
        return parent::send($fd, $data."\r\n", $fromId);
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @param string $moduleName
     */
    public function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }
}
