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
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use MongoDB\BSON\ObjectId;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Events\HandleJsonResponse;
use function Shadon\Helper\realpath;
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
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var callable
     */
    private $responseHandler;

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
     * @param callable    $responseHandler
     *
     * @throws \Exception
     */
    public function __construct(string $namespace, ClassLoader $classLoader, string $rootPath, callable $responseHandler)
    {
        $this->initRuntime($namespace, $rootPath);
        $this->classLoader = $classLoader;
        $this->responseHandler = $responseHandler;
        $this->registerService();
    }

    /**
     * @return int
     */
    public function main()
    {
        /* @var FpmContext $context */
        $context = $this->di->get(ContextInterface::class);
        $request = $context->get(Request::class);
        $dispatcher = FastRoute\simpleDispatcher($context->routeDefinitionCallback());
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
        $context->handle($routeInfo)->send();
    }

    private function registerService(): void
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->enableCompilation(realpath('var/cache'));
        $containerBuilder->writeProxiesToFile(true, realpath('var/cache'));
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAnnotations(true);
        $containerBuilder->addDefinitions(
            // loader
            [
                ClassLoader::class => $this->classLoader,
            ],
            // request ID
            [
                'requestId' => (string) new ObjectId(),
            ],
            // config
            [
                'config'=> DI\factory(function (): Repository {
                    return new Repository(require realpath(realpath('var/config/'.APP['env'].'/config.php')));
                }),
            ],
            // log
            [
                LoggerInterface::class => DI\factory(function (DI\Container $c): LoggerInterface {
                    $logger = new Logger(APP['namespace']);
                    $stream = realpath($c->get('config')->get('logPath')).'/app.'.date('Ymd').'.txt';
                    $fileHandler = new StreamHandler($stream);
                    $logger->pushHandler($fileHandler);

                    return $logger;
                }),
            ],
            // context
            [
                ContextInterface::class => DI\create(FpmContext::class)->property('di', Di\get(DI\Container::class)),
            ],
            // request
            [
                Request::class => DI\factory(function (DI\Container $c): Request {
                    return Request::createFromGlobals();
                }),
            ],
            // response handler
            [
                'responseHandler' => $this->responseHandler,
            ],
            // response
            [
                Response::class => DI\factory(function (DI\Container $c): Response {
                    //$c->get(DispatcherContract::class)->listen(HandleJsonResponse::class, $c->get('responseHandler'));
                    return JsonResponse::create(null, Response::HTTP_OK, ['content-type' => 'application/json', 'Server' => APP['serverName']]);
                }),
            ],
            // cache
            [
                CacheItemPoolInterface::class => DI\factory(function (DI\Container $c): CacheItemPoolInterface {
                    $context = $c->get(ContextInterface::class);
                    $cacheConfig = $context->moduleConfig('cache');
                    $redisClient = RedisAdapter::createConnection($cacheConfig['dsn'], $cacheConfig['options']);

                    return new RedisAdapter($redisClient, $cacheConfig['namespace'], $cacheConfig['defaultLifetime']);
                }),
            ],
            // events
            [
                DispatcherContract::class  => DI\create(Dispatcher::class),
            ],
            // mysql
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
    }

    /**
     * @param string $namespace
     * @param string $rootPath
     *
     * @throws \Exception
     */
    private function initRuntime(string $namespace, string $rootPath): void
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
            'env'        => $appEnv,
            'key'        => $appKey,
            'namespace'  => $namespace,
            'rootPath'   => $rootPath,
            'serverName' => self::SERVER_NAME,
         ]);
    }
}
