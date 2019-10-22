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
use Illuminate\Database\Capsule\Manager as Capsule;
use MongoDB\BSON\ObjectId;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
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
    private $transportHandler;

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
     * @param callable    $transportHandler
     *
     * @throws \Exception
     */
    public function __construct(string $namespace, ClassLoader $classLoader, callable $transportHandler)
    {
        $this->classLoader = $classLoader;
        $this->transportHandler = $transportHandler;
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
                'errorlogger' => DI\factory(function (DI\Container $c): LoggerInterface {
                    $logger = new Logger(APP['namespace']);
                    $stream = realpath($c->get('appConfig')->get('logPath')).'/app.'.date('Ymd').'.txt';
                    $fileHandler = new StreamHandler($stream);
                    $logger->pushHandler($fileHandler);

                    return $logger;
                }),
            ],
            [
                ContextInterface::class => DI\create(FpmContext::class),
            ],
            [
                CacheItemPoolInterface::class => DI\factory(function (DI\Container $c): CacheItemPoolInterface {
                    $context = $c->get(ContextInterface::class);
                    $cacheConfig = $context->moduleConfig('cache');
                    $redisClient = RedisAdapter::createConnection($cacheConfig['dsn'], $cacheConfig['options']);

                    return new RedisAdapter($redisClient, $cacheConfig['namespace'], $cacheConfig['defaultLifetime']);
                }),
            ],
            [
                Capsule::class => DI\factory(function (DI\Container $c): Capsule {
                    $context = $c->get(ContextInterface::class);
                    $mysqlConfig = $context->moduleConfig('mysql');
                    $capsule = new Capsule();
                    $capsule->addConnection($mysqlConfig);

                    return $capsule;
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
        $this->di->set('requestId', (string) new ObjectId());
        $transportHandler = $this->transportHandler;
        // handler error
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        set_error_handler(function ($code, $message, $file = '', $line = 0, $context = []): void {
            throw new ServerException($message, Response::HTTP_INTERNAL_SERVER_ERROR, '服务器异常');
        }, E_ALL);
        register_shutdown_function(function (DI\Container $di, $transportHandler): void {
            $this->reservedMemory = null;
            $lastError = error_get_last();
            $returnData = $transportHandler($di, new ServerException($lastError['message']));
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setData($returnData);
            $this->response->send();
            $di->get('errorlogger')->alert($lastError['message'], $lastError);
        }, $this->di, $transportHandler);
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
                $this->di->get('errorLogger')->error($e->getMessage(), [
                    'code'          => $e->getCode(),
                    'message'       => $message,
                    'class'         => \get_class($e),
                    'file'          => $e->getFile(),
                    'line'          => $e->getLine(),
                    'traceAsString' => $e->getTrace(),
                ]);
            }
            $returnData = $e;
            $return = 1;
        }
        $returnData = $transportHandler($this->di, $returnData);
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
            $r->addRoute('POST', '/{module:[a-z][a-zA-Z]*}/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}', function ($module, $controller, $action) {
                $appConfig = $this->di->get('appConfig');
                $moduleList = $appConfig->get('moduleList');
                if (!\in_array($module, $moduleList)) {
                    throw new NotFoundException(sprintf('moudule `%s` not found', $module));
                }
                $context = $this->di->get(ContextInterface::class);
                $context->setModuleName($module);
                $context->setController($controller);
                $context->setAction($action);
                // loader module class
                $moduleNamespace = APP['namespace'].'\\Module\\'.ucfirst($module);
                $this->classLoader->addPsr4($moduleNamespace.'\\', 'src/Module/'.ucfirst($module.'/'));
                $handlerClass = $moduleNamespace.'\\Logic\\'.ucfirst($controller).'Logic';
                if (!class_exists($handlerClass)) {
                    throw new NotFoundException(sprintf('handler `%s` not found', $controller));
                }
                // initial moudle instance
                $moduleInstance = $this->di->get($moduleNamespace.'\\Module');
                $moduleInstance->init();
                // check class and method
                try {
                    $reflectionMethod = new \ReflectionMethod($handlerClass, $action);
                } catch (\ReflectionException $e) {
                    throw new NotFoundException(sprintf('handler method `%s` not found', $action));
                }
                if (!'json' == $this->request->getContentType()) {
                    throw new RequestException('bad request, content type must json');
                }
                $data = json_decode($this->request->getContent(), true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new RequestException('bad request, content must json');
                }
                $context->setReflectionMethod($reflectionMethod);
                $parameters = $reflectionMethod->getParameters();
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
                $context->setParams($params);
                $context->push(function (ContextInterface $context) {
                    // init handler
                    $reflectionMethod = $context->getReflectionMethod();

                    return (new $reflectionMethod->class())->{$reflectionMethod->name}(...$context->getParams());
                });
                try {
                    return $context->next();
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
