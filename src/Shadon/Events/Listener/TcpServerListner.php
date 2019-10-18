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

namespace Shadon\Events\Listener;

use Exception;
use Phalcon\Dispatcher;
use Phalcon\Events\Event;
use Shadon\Network\TcpServer;

/**
 * Class TcpServerListner.
 */
class TcpServerListner
{
    public function onStart(TcpServer $server): void
    {
        $server->setProcessName('server');
        $info = sprintf('%s tcp server was started and listening on <info>%d</info>', $server->getModuleName(), $server->port);
        $server->writeln($info);
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(TcpServer $server, int $workerId): void
    {
        chdir(ROOT_PATH);
        $processName = $workerId >= $server->setting['worker_num'] ? 'task#'.$workerId : 'event#'.$workerId;
        $server->setProcessName($processName);
        // 清除apc或op缓存
        if (\function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (\function_exists('opcache_reset')) {
            opcache_reset();
        }
        $server->initializeModule();
        $server->registerRouter();
        $server->getDi()->setShared('server', $server);
        /* @var \Phalcon\Events\Manager $eventsManager */
        $eventsManager = $server->getDi()->getShared('eventsManager');
        $eventsManager->attach('dispatch:afterDispatchLoop', function (Event $event, Dispatcher $dispatcher) use ($server): void {
            $returnedValue = $dispatcher->getReturnedValue();
            /* @var \Phalcon\Http\Response $response */
            $response = $server->getDi()->getShared('response');
            if (\is_object($returnedValue)) {
                $response->setHeader('returnType', \get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $response->setJsonContent(['data' => $returnedValue, 'returnType' => \get_class($returnedValue)]);
                }
            } elseif (\is_array($returnedValue)) {
                $response->setHeader('returnType', 'array');
                $response->setJsonContent(['data' => $returnedValue, 'returnType' => 'array']);
            } elseif (is_scalar($returnedValue)) {
                $response->setHeader('returnType', \gettype($returnedValue));
                $response->setJsonContent(
                    ['data' => $returnedValue, 'returnType' => \gettype($returnedValue)]
                );
                if (\is_string($returnedValue)) {
                    $dispatcher->setReturnedValue($response->getContent());
                }
            }
        });
    }

    public function onWorkerStop(): void
    {
    }

    public function onConnect(): void
    {
    }

    public function onReceive(TcpServer $server, int $fd, int $reactorId, string $data): void
    {
        $data = \GuzzleHttp\json_decode($data, true);
        $uri = $data['uri'];
        $params = $data['params'];
        $di = $server->getDi();
        /* @var \Shadon\Router\ServiceRouter $router */
        $router = $di->getShared('router');
        $router->handle($uri);
        $router->setParams($params);
        /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->getShared('dispatcher');
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($params);
        /* @var \Phalcon\Http\Response $response */
        $response = $di->getShared('response');

        try {
            $controller = $dispatcher->dispatch();
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->setJsonContent(['error' => $e->getMessage(), 'returnType' => \get_class($e)]);
        }

        $server->send($fd, '{"content":'.$response->getContent().',"headers":'.json_encode($response->getHeaders()->toArray()).'}');
        $response->resetHeaders();
        $response->setContent('');
    }

    public function onPacket(): void
    {
    }

    public function onClose(): void
    {
    }

    public function onBufferFull(): void
    {
    }

    public function onBufferEmpty(): void
    {
    }

    public function onTask(): void
    {
    }

    public function onFinish(): void
    {
    }

    public function onPipeMessage(): void
    {
    }

    public function onWorkerError(): void
    {
    }

    public function onManagerStart(): void
    {
    }

    public function onManagerStop(): void
    {
    }
}
