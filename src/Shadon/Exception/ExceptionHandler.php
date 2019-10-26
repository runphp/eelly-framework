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

namespace Shadon\Exception;

use DI\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Shadon\Context\ContextInterface;
use Shadon\Events\BeforeResponseEvent;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExceptionHandler.
 *
 * @author hehui<runphp@qq.com>
 */
class ExceptionHandler extends SymfonyExceptionHandler
{
    /**
     * @var Container
     */
    private $di;

    /**
     * @return mixed
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * @param mixed $di
     */
    public function setDi(&$di): void
    {
        $this->di = &$di;
    }

    public function sendPhpResponse($exception): void
    {
        if (\is_object($this->di)) {
            if (!$exception instanceof FlattenException) {
                $exception = \Shadon\Exception\FlattenException::create($exception);
            }
            $dispatcher = $this->di->get(Dispatcher::class);
            $response = $this->di->get(Response::class);
            $context = $this->di->get(ContextInterface::class);
            $context->set('return', $exception);
            $dispatcher->dispatch(new BeforeResponseEvent($context));
            $response->setStatusCode($exception->getStatusCode());
            $response->setData($context->get('return'));
            $response->send();
        } else {
            parent::sendPhpResponse($exception);
        }
    }
}
