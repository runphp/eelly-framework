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

namespace Shadon\Context;

use Composer\Autoload\ClassLoader;
use DI\Container;
use FastRoute;
use Illuminate\Contracts\Events\Dispatcher;
use ReflectionMethod;
use Shadon\Events\BeforeResponseEvent;
use Shadon\Exception\MethodNotAllowedException;
use Shadon\Exception\NotFoundException;
use Shadon\Exception\RequestException;
use SplStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FpmContext.
 *
 * @author hehui<runphp@qq.com>
 */
class FpmContext implements ContextInterface
{
    use ContextTrait;

    /**
     * @var Container
     */
    private $di;

    /**
     * map.
     *
     * @var array
     */
    private $entries = [];

    /**
     * @var SplStack
     */
    private $handlerStack;

    public function __construct()
    {
        $this->handlerStack = new SplStack();
    }

    public function push(callable $handler): void
    {
        $this->handlerStack->push($handler);
    }

    public function next()
    {
        $handler = $this->handlerStack->shift();

        return $handler($this);
    }

    public function get($name)
    {
        if (isset($this->entries[$name])) {
            return $this->entries[$name];
        } else {
            return $this->di->get($name);
        }
    }

    public function set(string $name, $value): void
    {
        $this->entries[$name] = $value;
    }

    public function routeDefinitionCallback(): callable
    {
        return function (FastRoute\RouteCollector $routeCollector): void {
            $routeCollector->addRoute('GET', '/', function () {
                return 'Hello, I\'m '.APP['serverName'];
            });
            $routeCollector->addRoute('develop' == APP['env'] ? ['GET', 'POST'] : 'POST', '/{module:[a-z][a-zA-Z]*}/{controller:[a-z][a-zA-Z]*}/{action:[a-z][a-zA-Z]*}', function ($module, $controller, $action) {
                if (!\in_array($module, $this->get('config')->get('moduleList'))) {
                    throw new NotFoundException(sprintf('moudule `%s` not found', $module));
                }
                // loader module class
                $this->set('module', $module);
                $moduleNamespace = APP['namespace'].'\\Module\\'.ucfirst($module);
                $this->get(ClassLoader::class)->addPsr4($moduleNamespace.'\\', 'src/Module/'.ucfirst($module.'/'));
                $handlerClass = $moduleNamespace.'\\Logic\\'.ucfirst($controller).'Logic';
                if (!class_exists($handlerClass)) {
                    throw new NotFoundException(sprintf('handler `%s` not found', $controller));
                }

                // initial moudle instance
                $moduleInstance = $this->get($moduleNamespace.'\\Module');
                $moduleInstance->init();
                // check class and method
                try {
                    $reflectionMethod = new ReflectionMethod($handlerClass, $action);
                } catch (\ReflectionException $e) {
                    throw new NotFoundException(sprintf('handler method `%s` not found', $action));
                }
                $this->set(ReflectionMethod::class, $reflectionMethod);
                $parameters = $reflectionMethod->getParameters();
                $paramNum = $reflectionMethod->getNumberOfParameters();
                if (0 < $paramNum) {
                    $request = $this->get(Request::class);
                    if (!'json' == $request->getContentType()) {
                        throw new RequestException('bad request, content type must json');
                    }
                    $data = json_decode($request->getContent(), true);
                    if (JSON_ERROR_NONE !== json_last_error()) {
                        throw new RequestException('bad request, content must json');
                    }
                }
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
                $this->set('params', $params);

                $this->push(function (ContextInterface $context) {
                    // init handler
                    $reflectionMethod = $context->get(ReflectionMethod::class);
                    $hander = $this->get($reflectionMethod->class);
                    $this->set('hander', $hander);

                    return $hander->{$reflectionMethod->name}(...$context->get('params'));
                });

                try {
                    return $this->next();
                } catch (\TypeError $e) {
                    throw new RequestException($e->getMessage());
                }
            });
        };
    }

    public function handle(array $routeInfo): Response
    {
        if (FastRoute\Dispatcher::FOUND == $routeInfo[0]) {
            $data = $routeInfo[1](...array_values($routeInfo[2]));
        } elseif (FastRoute\Dispatcher::NOT_FOUND == $routeInfo[0]) {
            $data = new NotFoundException();
        } elseif (FastRoute\Dispatcher::METHOD_NOT_ALLOWED == $routeInfo[0]) {
            $data = new MethodNotAllowedException();
        }
        // ready for response
        /* @var Dispatcher $dispatcher */
        $dispatcher = $this->get(Dispatcher::class);
        $response = $this->get(Response::class);
        $dispatcher->dispatch(new BeforeResponseEvent($this, $data));

        return $response;
    }
}
