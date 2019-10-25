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
use Shadon\Exception\NotFoundException;
use Shadon\Exception\RequestException;

/**
 * McaHandler.
 *
 * McaHanler is module -> controller -> action handler
 *
 * @author hehui<runphp@qq.com>
 */
class McaHandler
{
    public function __invoke(ContextInterface $context)
    {
        return (function (string $module, string $controller, string $action) {
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
                $this->set(\ReflectionMethod::class, $reflectionMethod = new \ReflectionMethod($handlerClass, $action));
            } catch (\ReflectionException $e) {
                throw new NotFoundException(sprintf('handler method `%s` not found', $action));
            }
            // prepare params
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
            // push handler
            $this->push(function (ContextInterface $context) use ($reflectionMethod) {
                // init handler
                $hander = $this->get($reflectionMethod->class);
                $this->set('hander', $hander);

                return $hander->{$reflectionMethod->name}(...$context->get('params'));
            });
            // run handler
            try {
                return $this->next();
            } catch (\TypeError $e) {
                throw new RequestException($e->getMessage());
            }
        })->bindTo($context);
    }
}
