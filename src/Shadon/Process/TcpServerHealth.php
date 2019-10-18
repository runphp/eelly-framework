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

namespace Shadon\Process;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use Shadon\Network\TcpServer as Server;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 服务器健康状态进程.
 */
class TcpServerHealth extends Process
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(Server $server = null, bool $redirectStdinStdout = false, int $createPipe = 2)
    {
        parent::__construct([$this, 'processhandler'], $redirectStdinStdout, $createPipe);
        $this->server = $server;
        $this->httpClient = new HttpClient(['timeout' => 2, 'http_errors' => false]);
    }

    public function processhandler(self $serverMonitor): void
    {
        $this->server->setProcessName('health');
        $this->registerModule();
        // 1s
        $this->server->tick(1000, function (): void {
            //...
        });
        // 10s
        $this->server->tick(10000, function (): void {
            $this->registerModule();
        });
    }

    public function registerModule(): void
    {
        $di = $this->server->getDi();
        $port = $di->getShared('config')->httpServer->port;

        try {
            $response = $this->httpClient->post(
                '0.0.0.0:'.$port.'/_/tcpServer/register',
                [
                    'form_params' => [
                        'module'  => $this->server->getModuleName(),
                        'port'    => $this->server->port,
                        'updated' => time(),
                    ],
                ]
            );
            if (200 == $response->getStatusCode()) {
                $this->server->writeln(
                    sprintf(
                        'register module(%s) to 0.0.0.0:%d %s',
                        $this->server->getModuleName(), $port, $body = (string) $response->getBody()
                    ),
                    OutputInterface::VERBOSITY_DEBUG
                );
                foreach (\GuzzleHttp\json_decode($body, true) as $module => $value) {
                    $this->server->registerModule($module, $value['ip'], $value['port']);
                }
            }
        } catch (ConnectException $e) {
            $this->server->writeln($e->getMessage());
        }
    }
}
