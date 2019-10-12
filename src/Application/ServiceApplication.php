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
use League\OAuth2\Server\Exception\OAuthServerException;
use LogicException;
use MongoDB\BSON\ObjectId;
use Phalcon\Config;
use Phalcon\Dispatcher;
use Phalcon\Events\Event;
use Phalcon\Mvc\Router;
use Shadon\Di\ServiceDi;
use Shadon\Error\Handler as ErrorHandler;
use Shadon\Exception\RequestException;
use Shadon\Mvc\Application;

/**
 * @property \Shadon\Mvc\Application $application
 *
 * @author hehui<hehui@eelly.net>
 */
class ServiceApplication
{
    /**
     * @var Application
     */
    private $application;

    private $di;

    /**
     * ServiceApplication constructor.
     *
     * @param ClassLoader $classLoader
     */
    public function __construct(ClassLoader $classLoader)
    {
        $this->di = new ServiceDi();
        $this->di->setShared('loader', $classLoader);
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APPLICATION_KEY=/m',
                    'APPLICATION_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = new \Dotenv\Dotenv(getcwd(), '.env');
        $dotenv->load();
        $appEnv = getenv('APPLICATION_ENV');
        $appKey = getenv('APPLICATION_KEY');
        $arrayConfig = require 'var/config/config.'.$appEnv.'.php';
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $arrayConfig['requestTime'] = $_SERVER['REQUEST_TIME_FLOAT'];
        } elseif (isset($_SERVER['REQUEST_TIME'])) {
            $arrayConfig['requestTime'] = $_SERVER['REQUEST_TIME'];
        } else {
            $arrayConfig['requestTime'] = microtime(true);
        }
        \define('APP', [
            'env'       => $appEnv,
            'key'       => $appKey,
            'timezone'  => $arrayConfig['timezone'],
            'appname'   => $arrayConfig['appName'],
            'requestId' => (string) new ObjectId(),
        ]);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_FPM | ApplicationConst::RUNTIME_ENV_SERVICE);
        $this->di->setShared('config', new Config($arrayConfig));
        date_default_timezone_set(APP['timezone']);
        $this->application = $this->di->getShared(Application::class);
        $this->di->setShared('application', $this->application);
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        /* @var ErrorHandler $errorHandler */
        $errorHandler = $this->di->getShared(ErrorHandler::class);
        $errorHandler->register();
        $this->attachEvents();
        foreach ($this->di->getShared('config')->appBundles as $bundle) {
            $this->di->getShared($bundle)->register();
        }
        $modules = [];
        $router = $this->di->getShared('router');
        foreach ($this->di->getShared('config')->moduleList as $moduleName) {
            $namespace = ucfirst($moduleName);
            $router->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace.'\\Logic',
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ]);
            $modules[$moduleName] = [
                'className' => $namespace.'\\Module',
                'path'      => 'src/'.$namespace.'/Module.php',
            ];
        }
        $this->application->registerModules($modules);
        $response = $this->di->getShared('response');

        try {
            $this->application->handle($uri);
        } catch (LogicException $e) {
            $response->setHeader('returnType', \get_class($e));
            $content = ['error' => $e->getMessage(), 'returnType' => \get_class($e)];
            if (method_exists($e, 'getContext')) {
                $content['context'] = $e->getContext();
            }
            $response->setJsonContent($content);
        } catch (RequestException $e) {
            $response = $e->getResponse();
        } catch (OAuthServerException $e) {
            $response->setStatusCode($e->getHttpStatusCode());
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        }
        if (isset($e)) {
            $this->di->getShared('eventsManager')->fire('application:beforeSendResponse', $this->application, $response);
        }

        return $response;
    }

    /**
     * run.
     */
    public function run(): void
    {
        $this->handle()->send();
    }

    private function attachEvents()
    {
        /* @var \Phalcon\Events\Manager $eventsManager */
        $eventsManager = $this->di->getShared('eventsManager');
        $eventsManager->attach('dispatch:afterDispatchLoop', function (Event $event, Dispatcher $dispatcher): void {
            $returnedValue = $dispatcher->getReturnedValue();
            $response = $this->di->getShared('response');
            if (\is_object($returnedValue)) {
                $response->setHeader('returnType', \get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $response->setJsonContent(['data' => $returnedValue, 'returnType' => \get_class($returnedValue)]);
                }
            } elseif (\is_array($returnedValue)) {
                $response->setHeader('returnType', 'array');
                $response->setJsonContent(['data' => $returnedValue, 'returnType' => 'array']);
            } elseif (is_scalar($returnedValue)) {
                /* @var \ReflectionMethod $classMethod */
                $classMethod = $dispatcher->getDispatchMethod();
                $returnType = $classMethod->getReturnType();
                $returnTypeName = null === $returnType ? \gettype($returnedValue) : $returnType->getName();
                $returnTypeName = 'void' == $returnTypeName ? 'null' : $returnTypeName;
                settype($returnedValue, $returnTypeName);
                $response->setHeader('returnType', $returnTypeName);
                $response->setJsonContent(
                    ['data' => $returnedValue, 'returnType' => $returnTypeName]
                );
                $dispatcher->setReturnedValue($response->getContent());
            } elseif (null === $returnedValue && empty($response->getContent())) {
                $response->setJsonContent(
                    ['data' => null, 'returnType' => 'null']
                );
            }
        });
        $eventsManager->attach('router:afterCheckRoutes', function (Event $event, Router $router): void {
            /* @var \Shadon\Http\ServiceRequest $request */
            $request = $this->di->getShared('request');
            if ($request->isPost()) {
                $router->setParams($request->getRouteParams());
            }
        });
        $this->application->setEventsManager($eventsManager);

        return $this;
    }
}
