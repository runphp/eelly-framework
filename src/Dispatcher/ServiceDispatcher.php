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

namespace Shadon\Dispatcher;

use InvalidArgumentException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;
use Shadon\Application\ApplicationConst;
use Shadon\Exception\RequestException;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceDispatcher extends Dispatcher
{
    /**
     * 表单提交的空数组.
     *
     * @var string
     */
    public const FORM_EMPTY_ARRAY_STR = '/*_EMPTY_ARRAY_*/';

    /**
     * @var \ReflectionMethod
     */
    private $dispatchMethod;

    /**
     * ServiceDispatcher constructor.
     */
    public function __construct()
    {
        $this->setControllerSuffix('Logic');
        $this->setActionSuffix('');
    }

    public function afterServiceResolve(): void
    {
        $this->getEventsManager()->attach('dispatch', $this);
    }

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     * @param Exception  $exception
     *
     * @return bool
     */
    public function beforeException(Event $event, Dispatcher $dispatcher, \Exception $exception)
    {
        $notFoundFuntion = function (): void {
            $response = $this->getDI()->getShared('response');
            $response->setStatusCode(404);
            $response->setJsonContent([
                'error' => 'Not found',
            ]);
        };
        if ($exception instanceof DispatchException) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $notFoundFuntion();

                    return false;
            }
        }

        return true;
    }

    /**
     * @param array $routeParams
     */
    public function setParams($routeParams): void
    {
        $class = $this->getControllerClass();
        $method = $this->getActionName();
        /**
         * @var \Shadon\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getShared('request');
        if (!class_exists($class) || !method_exists($class, $method)) {
            throw new RequestException(404, sprintf('Not Found %s', $request->getURI()), $request, $this->getDI()->getShared('response'));
        }
        ApplicationConst::setRequestAction($class.':'.$method);
        $classMethod = new \ReflectionMethod($class, $method);
        $this->dispatchMethod = $classMethod;
        $parameters = $classMethod->getParameters();
        $parametersNumber = $classMethod->getNumberOfParameters();
        if (0 != $parametersNumber) {
            $this->filterRouteParams($routeParams, $parameters);
        }
        ksort($routeParams);
        $requiredParametersNumber = $classMethod->getNumberOfRequiredParameters();
        $actualParametersNumber = \count($routeParams);
        if ($actualParametersNumber < $requiredParametersNumber) {
            $this->throwInvalidArgumentException(
                sprintf('Too few arguments, %d passed and at least %d expected', $actualParametersNumber, $requiredParametersNumber)
            );
        }
        parent::setParams($routeParams);
    }

    /**
     * @return \ReflectionMethod
     */
    public function getDispatchMethod()
    {
        return $this->dispatchMethod;
    }

    /**
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    private function throwInvalidArgumentException($message): void
    {
        $response = $this->getDI()->getShared('response');
        $response->setStatusCode(400);

        throw new InvalidArgumentException($message);
    }

    /**
     * filter route params.
     *
     * @param array $routeParams
     * @param array $parameters
     */
    private function filterRouteParams(array &$routeParams, array $parameters): void
    {
        $functionOfThrowInvalidArgumentException = function ($position, $expectedType, $actualType): void {
            $this->throwInvalidArgumentException(sprintf('Argument %d must be of the type %s, %s given', $position, $expectedType, $actualType));
        };
        $functionReplaceEmptyArray = function (array $params) use (&$functionReplaceEmptyArray) {
            $return = [];
            foreach ($params as $key => $value) {
                if (\is_array($value) && !empty($value)) {
                    $return[$key] = $functionReplaceEmptyArray($value);
                    continue;
                }
                if (self::FORM_EMPTY_ARRAY_STR === $value) {
                    $return[$key] = [];
                } else {
                    $return[$key] = $value;
                }
            }

            return $return;
        };
        /**
         * @var \ReflectionParameter $parameter
         */
        foreach ($parameters as $parameter) {
            $position = $parameter->getPosition();
            $expectedType = (string) $parameter->getType();
            $checkedParameter = false;
            // 构建缺失的参数
            if (!isset($routeParams[$position])) {
                $paramName = $parameter->getName();
                if (isset($routeParams[$paramName])) {
                    // 存在变量名参数
                    $routeParams[$position] = $routeParams[$paramName];
                    unset($routeParams[$paramName]);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    // 存在默认值参数
                    $routeParamsObject = new \ArrayObject($routeParams);
                    $status = $this->getEventsManager()->fire('dispatcher:setDefaultParamValue', $routeParamsObject, $parameter);
                    if (false === $status) {
                        $routeParams = $routeParamsObject->getArrayCopy();
                    } else {
                        $routeParams[$position] = $parameter->getDefaultValue();
                    }
                    $checkedParameter = true;
                } else {
                    $functionOfThrowInvalidArgumentException($position, $expectedType, 'null');
                }
            }
            // 校验参数
            if (array_key_exists($position, $routeParams)) {
                if (!$checkedParameter) {
                    if (\in_array($expectedType, ['bool', 'int', 'float', 'string', 'array'])) {
                        if (\is_array($routeParams[$position]) && 'array' != $expectedType) {
                            $functionOfThrowInvalidArgumentException($position, $expectedType, 'array');
                        }
                        if (self::FORM_EMPTY_ARRAY_STR === $routeParams[$position]) {
                            $routeParams[$position] = [];
                        } else {
                            \settype($routeParams[$position], $expectedType);
                            if (\is_array($routeParams[$position]) && !empty($routeParams[$position])) {
                                $routeParams[$position] = $functionReplaceEmptyArray($routeParams[$position]);
                            }
                        }
                    } elseif (!empty($expectedType) && !is_a($routeParams[$position], $expectedType)) {
                        $functionOfThrowInvalidArgumentException($position, $expectedType, \gettype($routeParams[$position]));
                    }
                }
            } else {
                $functionOfThrowInvalidArgumentException($position, $expectedType, 'null');
            }
        }

        foreach ($routeParams as $key => $value) {
            if (!\is_int($key)) {
                $this->throwInvalidArgumentException("Unidentified argument $key");
            }
        }
    }
}
