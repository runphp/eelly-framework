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
use Phalcon\Events\Event;
use Phalcon\Mvc\Router;
use Shadon\Error\Handler as ErrorHandler;
use Shadon\Exception\RequestException;
use Shadon\Http\SwoolePhalconRequest;
use Shadon\Network\HttpServer;
use swoole_http_request as SwooleHttpRequest;
use swoole_http_response as SwooleHttpResponse;

class HttpServerListener
{
    /**
     * @var HttpServer
     */
    private $server;

    public function onStart(HttpServer $server): void
    {
        $server->setProcessName('server');
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(HttpServer $server, int $workerId): void
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
        // 注册路由
        $server->registerRouter();
        $this->server = $server;
        $server->getDi()->setShared('server', $server);
        /* @var \Phalcon\Events\Manager $eventsManager */
        $eventsManager = $server->getDi()->getShared('eventsManager');
        $eventsManager->attach('router:afterCheckRoutes', function (Event $event, Router $router) use ($server): void {
            /* @var \Shadon\Http\ServiceRequest $request */
            $request = $server->getDi()->getShared('request');
            if ($request->isPost()) {
                $router->setParams($request->getRouteParams());
            }
        });
        $errorHandler = $server->getDi()->getShared(ErrorHandler::class);
        $errorHandler->register();
    }

    public function onWorkerStop(HttpServer $server, int $workerId): void
    {
    }

    public function onRequest(SwooleHttpRequest $swooleHttpRequest, SwooleHttpResponse $swooleHttpResponse): void
    {
        if ('/favicon.ico' == $swooleHttpRequest->server['request_uri']) {
            $swooleHttpResponse->status(404);
            $swooleHttpResponse->end();

            return;
        }
        $di = $this->server->getDi();

        /* @var SwoolePhalconRequest  $phalconHttpRequest */
        $phalconHttpRequest = $di->get('request');
        $phalconHttpRequest->initialWithSwooleHttpRequest($swooleHttpRequest);

        /* @var \Shadon\Router\ServiceRouter $router */
        $router = $di->getShared('router');
        $router->handle();
        $moduleName = $router->getModuleName();
        /* @var \Phalcon\Http\Response $response */
        $response = $di->getShared('response');
        $response->setStatusCode(200);
        $response->setContentType('application/json');
        if (null === $moduleName) {
            /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
            $dispatcher = $di->getShared('dispatcher');
            $dispatcher->setModuleName($router->getModuleName());
            $dispatcher->setNamespaceName($router->getNamespaceName());
            $dispatcher->setControllerName($router->getControllerName());
            $dispatcher->setActionName($router->getActionName());
            $dispatcher->setParams($router->getParams());

            try {
                $controller = $dispatcher->dispatch();
                $possibleResponse = $dispatcher->getReturnedValue();
                if ($possibleResponse instanceof \Phalcon\Mvc\View) {
                    // doc
                    $possibleResponse->start();
                    $possibleResponse->render(
                        $dispatcher->getControllerName(),
                        $dispatcher->getActionName(),
                        $dispatcher->getParams()
                    );
                    $content = $possibleResponse->getContent();
                    $possibleResponse->finish();
                    $response->setContentType('text/html');
                    $response->setContent($content);
                } else {
                    // system api
                    $response->setJsonContent($possibleResponse);
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();
            } catch (\Exception $e) {
                $response->setStatusCode(500);
                $response->setJsonContent(['error' => $e->getMessage()]);
            }
        } else {
            // service api
            $response->setContentType('application/json');

            try {
                $moduleClient = $this->server->getModuleClient($moduleName);
                $moduleClient->sendJson([
                    'uri'    => $router->getRewriteUri(),
                    'params' => $router->getParams(),
                ]);
                $data = $moduleClient->recvJson();
                $response->setJsonContent($data['content']);
                foreach ($data['headers'] as $key => $value) {
                    $response->setHeader($key, $value);
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();
            } catch (Exception $e) {
                $response->setStatusCode(500);
                $response->setJsonContent(['error' => $e->getMessage(), 'returnType' => \get_class($e)]);
            }
        }
        // swollow output
        $swooleHttpResponse->status($response->getStatusCode());
        foreach ($response->getHeaders()->toArray() as $key => $value) {
            $swooleHttpResponse->header($key, (string) $value);
        }
        $swooleHttpResponse->end($response->getContent());

        $response->resetHeaders();
        $response->setContent('');
    }

    public function onPacket(): void
    {
    }

    public function onClose(HttpServer $server, int $fd, int $reactorId): void
    {
    }

    public function onBufferFull(): void
    {
    }

    public function onBufferEmpty(): void
    {
    }

    public function onTask(HttpServer $server, int $taskId, int $workId, $data)
    {
        if (isset($data['class']) && method_exists($data['class'], $data['method'])) {
            return \call_user_func_array([new $data['class'](), $data['method']], $data['params']);
        }
    }

    public function onFinish(HttpServer $server, int $taskId, $data): void
    {
    }

    public function onPipeMessage(HttpServer $server, int $workId, string $message): void
    {
    }

    public function onWorkerError(HttpServer $server, int $workerId, int $workerPid, int $exitCode, int $signal): void
    {
        $server->shutdown();
    }

    public function onManagerStart(HttpServer $server): void
    {
        $server->setProcessName('manager');
    }

    public function onManagerStop(HttpServer $server): void
    {
    }
}
