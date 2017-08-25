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

namespace Eelly\Dispatcher;

use Eelly\Doc\ApiDoc;
use Eelly\DTO\UidDTO;
use Eelly\Exception\RequestException;
use InvalidArgumentException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceDispatcher extends Dispatcher
{
    /**
     * @var UidDTO
     */
    public static $uidDTO;

    public function afterServiceResolve(): void
    {
        $this->setControllerSuffix('Logic');
        $this->setActionSuffix('');
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

                    return true;
            }

            return true;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Dispatcher::setParams()
     */
    public function setParams($routeParams): void
    {
        /**
         * @var \Eelly\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getShared('request');
        if ($request->isPost()) {
            $this->setSeviceParams($routeParams);
        } elseif ($request->isGet()) {
            $module = $this->getModuleName();
            $class = parent::getHandlerClass();
            $method = $this->getActionName();
            $this->setControllerSuffix('');
            $this->setControllerName(ApiDoc::class);
            $this->setActionName('display');
            parent::setParams([$module, $class, $method]);
        } else {
            // Method Not Allowed
            throw new RequestException(405, null, $this->getDI()->getShared('request'), $this->getDI()->getShared('response'));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Dispatcher::getHandlerClass()
     */
    public function getHandlerClass()
    {
        $request = $this->getDI()->getShared('request');
        if ($request->isGet()) {
            return ApiDoc::class;
        } else {
            return parent::getHandlerClass();
        }
    }

    /**
     * @param array $routeParams
     */
    private function setSeviceParams(array $routeParams): void
    {
        $class = $this->getControllerClass();
        $method = $this->getActionName();
        /**
         * @var \Eelly\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getShared('request');
        if (!class_exists($class) || !method_exists($class, $method)) {
            throw new RequestException(404, null, $request, $this->getDI()->getShared('response'));
        }

        $classMethod = new \ReflectionMethod($class, $method);
        $parameters = $classMethod->getParameters();
        $parametersNumber = $classMethod->getNumberOfParameters();
        if (0 != $parametersNumber) {
            $this->filterRouteParams($routeParams, $parameters);
        }
        ksort($routeParams);
        $requiredParametersNumber = $classMethod->getNumberOfRequiredParameters();
        $actualParametersNumber = count($routeParams);
        if ($actualParametersNumber < $requiredParametersNumber) {
            $this->throwInvalidArgumentException(
                sprintf('Too few arguments, %d passed and at least %d expected', $actualParametersNumber, $requiredParametersNumber)
            );
        }
        parent::setParams($routeParams);
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
                    if ($expectedType == UidDTO::class) {
                        $routeParams[$position] = self::$uidDTO = new UidDTO();
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
                    if (in_array($expectedType, ['bool', 'int', 'float', 'string', 'array'])) {
                        if (is_array($routeParams[$position]) && 'array' != $expectedType) {
                            $functionOfThrowInvalidArgumentException($position, $expectedType, 'array');
                        }
                        settype($routeParams[$position], $expectedType);
                    } elseif (!is_a($routeParams[$position], $expectedType)) {
                        $functionOfThrowInvalidArgumentException($position, $expectedType, gettype($routeParams[$position]));
                    }
                }
            } else {
                $functionOfThrowInvalidArgumentException($position, $expectedType, 'null');
            }
        }
    }
}
