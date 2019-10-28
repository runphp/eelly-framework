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
use DI\Annotation\Inject;
use Shadon\Exception\NotFoundException;
use Shadon\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * McaHandler.
 *
 * McaHanler is module -> controller -> action handler
 *
 * @author hehui<runphp@qq.com>
 */
class McaHandler
{
    /**
     * @Inject
     *
     * @var ContextInterface
     */
    private $context;

    public function __invoke()
    {
        return (function (string $module, string $controller, string $action, string $NS = 'API') {
            if (!\in_array($module, $this->context->get('config')->get('moduleList'))) {
                throw new NotFoundException(sprintf('moudule `%s` not found', $module));
            }
            $this->context->set('controller', $controller);
            $this->context->set('action', $action);
            // loader module class·
            $handlerClass = $this->loadModuleClass($module, $controller, $NS);
            // check class and method
            $this->validateHandler($handlerClass, $action);
            // push handler
            $this->context->push(function (ContextInterface $context) {
                // init handler
                $reflectionMethod = $context->get(\ReflectionMethod::class);
                $hander = $context->get($reflectionMethod->class);
                $context->set('hander', $hander);

                return $hander->{$reflectionMethod->name}(...$context->get('params'));
            });
            // run handler
            try {
                return $this->context->next();
            } catch (\TypeError $e) {
                throw new RequestException($e->getMessage());
            }
        })->bindTo($this);
    }

    private function loadModuleClass(string $module, string $controller, string $NS): string
    {
        $this->context->set('module', $module);
        $moduleNamespace = APP['namespace'].'\\Module\\'.ucfirst($module);
        $this->context->get(ClassLoader::class)->addPsr4($moduleNamespace.'\\', 'src/Module/'.ucfirst($module.'/'));
        $handlerClass = sprintf('%s\\%s\\%s%s', $moduleNamespace, $NS, ucfirst($controller), $NS);
        if (!class_exists($handlerClass)) {
            throw new NotFoundException(sprintf('handler `%s` not found', $controller));
        }
        $this->context->set('handlerNS', $NS);
        // initial moudle instance
        $moduleInstance = $this->context->get($moduleNamespace.'\\Module');
        $moduleInstance->init();

        return $handlerClass;
    }

    private function validateHandler(string $handlerClass, string $action): void
    {
        try {
            $this->context->set(\ReflectionMethod::class, $reflectionMethod = new \ReflectionMethod($handlerClass, $action));
        } catch (\ReflectionException $e) {
            throw new NotFoundException(sprintf('handler method `%s` not found', $action));
        }
        $parameters = $reflectionMethod->getParameters();
        $paramNum = $reflectionMethod->getNumberOfParameters();
        if (0 < $paramNum) {
            $request = $this->context->get(Request::class);
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
        $this->context->set('params', $params);
    }
}
