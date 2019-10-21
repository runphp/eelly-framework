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

namespace Shadon\Application;

use Composer\Autoload\ClassLoader;
use DI;
use FastRoute;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use MongoDB\BSON\ObjectId;
use Shadon\Exception\ClientException;
use Shadon\Exception\Exception;
use Shadon\Exception\LogicException;
use Shadon\Exception\MethodNotAllowedException;
use Shadon\Exception\NotFoundException;
use Shadon\Exception\RequestException;
use Shadon\Exception\ServerException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MacroApplication.
 *
 * hehui<runphp@qq.com>.
 */
class MicroApplication
{
    /**
     * server name.
     *
     * @var string
     */
    public const SERVER_NAME = 'Shadon/v2.0';

    /**
     * @var Di\Container
     */
    private $di;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var FastRoute\Dispatcher\GroupCountBased
     */
    private $dispatcher;

    /**
     * @var JsonResponse
     */
    private $response;

    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var callable
     */
    private $transportFunc;

    /**
     * reserved memory.
     *
     * @var string
     */
    private $reservedMemory;

    /**
     * MacroApplication constructor.
     *
     * @param string      $namespace
     * @param ClassLoader $classLoader
     * @param callable    $transportFunc
     *
     * @throws \Exception
     */
    public function __construct(string $namespace, ClassLoader $classLoader, callable $transportFunc)
    {
        $this->classLoader = $classLoader;
        $this->transportFunc = $transportFunc;
        $this->initRuntime($namespace);
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(ROOT_PATH.'/var/cache');
        $containerBuilder->writeProxiesToFile(true, ROOT_PATH.'/var/proxies');
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $containerBuilder->addDefinitions(
            [
                'appConfig'=> DI\factory(function () {
                    return new Repository(require 'var/config/'.APP['env'].'/config.php');
                }),
            ],
            [
                'createMysqlService' => DI\factory(function () {
                    return function (array $config): Capsule {
                        $capsule = new Capsule();
                        $capsule->addConnection($config);
                        $capsule->setEventDispatcher(new Dispatcher(new Container()));
                        $capsule->setAsGlobal();
                        $capsule->bootEloquent();

                        return $capsule;
                    };
                }),
                'createCacheService' => DI\factory(function (DI\Container $c) {
                    return function (array $hosts, string $namespace) use ($c): RedisAdapter {
                        $redisConfig = [
                            'dsn'     => 'redis:?host['.implode(']&host[', $hosts).']&redis_cluster=1',
                            'options' => [
                                'compression'    => true,
                                'lazy'           => true,
                                'persistent'     => 0,
                                'persistent_id'  => null,
                                'tcp_keepalive'  => 0,
                                'timeout'        => 10,
                                'read_timeout'   => 10,
                                'retry_interval' => 0,
                            ],
                        ];
                        $redisClient = RedisAdapter::createConnection($redisConfig['dsn'], $redisConfig['options']);
                        $cache = new RedisAdapter($redisClient, $namespace, 300);
                        $c->set('cache', $cache);

                        return $cache;
                    };
                }),
            ]
         );
        $this->di = $containerBuilder->build();
        $this->initService();
    }

    /**
     * @return int
     */
    public function main(): int
    {
        $requestId = (string) new ObjectId();
        $transportFunc = $this->transportFunc;
        // handler error
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        set_error_handler(function ($code, $message, $file = '', $line = 0, $context = []): void {
            throw new ServerException($message, Response::HTTP_INTERNAL_SERVER_ERROR, '服务器异常');
        }, E_ALL);
        register_shutdown_function(function ($requestId, $transportFunc): void {
            $this->reservedMemory = null;
            $lastError = error_get_last();
            // TODO debug file line
            $returnData = $transportFunc($requestId, new ServerException($lastError['message']));
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setData($returnData);
            $this->response->send();
        // TOTO error log
        }, $requestId, $transportFunc);
        $this->reservedMemory = str_repeat('X', 20480);

        $routeInfo = $this->dispatcher->dispatch($this->request->getMethod(), $this->request->getPathInfo());

        try {
            $returnData = $this->handleRouteInfo($routeInfo);
        } catch (LogicException $e) {
            $returnData = $e;
            $return = 0;
        } catch (Exception $e) {
            $this->response->setStatusCode($e->getCode());
            if (!$e instanceof ClientException) {
                // TOTO error log
            }
            $returnData = $e;
            $return = 1;
        }
        $returnData = $transportFunc($requestId, $returnData);
        $this->response->setData($returnData);
        $this->response->send();

        return $return;
    }

    private function handleRouteInfo(array $routeInfo)
    {
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundException();
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException();
            case FastRoute\Dispatcher::FOUND: // 找到对应的方法
                $handler = $routeInfo[1]; // 获得处理函数
                $vars = $routeInfo[2]; // 获取请求参数
                $returnData = $handler(...array_values($vars));
        }

        return $returnData;
    }

    /**
     * @param string $namespace
     *
     * @throws \Exception
     */
    private function initRuntime(string $namespace): void
    {
        // created default .env
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APP_KEY=/m',
                    'APP_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = \Dotenv\Dotenv::create(getcwd());
        $dotenv->load();
        $appEnv = getenv('APP_ENV');
        $appKey = getenv('APP_KEY');
        \define('APP', [
            'namespace'  => $namespace,
            'env'        => $appEnv,
            'key'        => $appKey,
         ]);
    }

    private function initService(): void
    {
        $this->di->set('request', $this->request = Request::createFromGlobals());
        $this->di->set('response', $this->response = JsonResponse::create(null, Response::HTTP_OK, ['content-type' => 'application/json', 'Server' => self::SERVER_NAME]));
        $this->dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r): void {
            $r->addRoute('GET', '/', function () {
                return 'Hello, I\'m '.self::SERVER_NAME;
            });
            $r->addRoute('POST', '/{module:[a-z][a-zA-Z]*}/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}', function ($module, $controller, $method) {
                $appConfig = $this->di->get('appConfig');
                $moduleList = $appConfig->get('moduleList');
                if (!\in_array($module, $moduleList)) {
                    throw new NotFoundException(sprintf('moudule `%s` not found', $module));
                }
                // loader module class
                $moduleNamespace = APP['namespace'].'\\Module\\'.ucfirst($module);
                $this->classLoader->addPsr4($moduleNamespace.'\\', 'src/Module/'.ucfirst($module.'/'));
                $handlerClass = $moduleNamespace.'\\Logic\\'.ucfirst($controller).'Logic';
                if (!class_exists($handlerClass)) {
                    throw new NotFoundException(sprintf('handler `%s` not found', $controller));
                }
                // initial moudle instance
                $moduleInstance = $this->di->get($moduleNamespace.'\\Module');
                $this->di->set('module', $moduleInstance);
                $moduleInstance->registerService(function (string $name) use ($module) {
                    return require sprintf('var/config/%s/%s/%s.php', APP['env'], $module, $name);
                });
                $moduleInstance->registerEvent();
                // init handler
                $handlerInstance = $this->di->get($handlerClass);
                if (!method_exists($handlerInstance, $method)) {
                    throw new NotFoundException(sprintf('handler method `%s` not found', $method));
                }
                if (!'json' == $this->request->getContentType()) {
                    throw new RequestException('bad request, content type must json');
                }
                $data = json_decode($this->request->getContent(), true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new RequestException('bad request, content must json');
                }
                $classMethod = new \ReflectionMethod($handlerClass, $method);
                $parameters = $classMethod->getParameters();
                $params = [];
                foreach ($parameters as $parameter) {
                    $paramName = $parameter->getName();
                    if (isset($data[$paramName])) {
                        // exist
                        $params[] = $data[$paramName];
                    } elseif ($parameter->isDefaultValueAvailable()) {
                        // has default
                        $params[] = $parameter->getDefaultValue();
                    } else {
                        throw new RequestException(sprintf('bad request, param `%s` is required', $paramName));
                    }
                }
                try {
                    $return = $handlerInstance->$method(...$params);
                } catch (\TypeError $e) {
                    throw new RequestException($e->getMessage());
                } catch (\Throwable $e) {
                    if ($e instanceof Exception) {
                        throw $e;
                    } else {
                        throw new ServerException($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, '服务器异常', $e);
                    }
                }

                return $return;
            });
        });
    }
}
