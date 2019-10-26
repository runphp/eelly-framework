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
     * @var ContextInterface
     */
    private $context;

    /**
     * @return ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    /**
     * @param ContextInterface $context
     */
    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function sendPhpResponse($exception): void
    {
        if (\is_object($this->context)) {
            if (!$exception instanceof FlattenException) {
                $exception = \Shadon\Exception\FlattenException::create($exception);
            }
            $dispatcher = $this->context->get(Dispatcher::class);
            $response = $this->context->get(Response::class);
            $context = $this->context->get(ContextInterface::class);
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
