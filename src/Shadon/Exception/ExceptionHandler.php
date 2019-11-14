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
use Psr\Http\Message\ResponseInterface;
use Shadon\Context\ContextInterface;
use Shadon\Events\BeforeResponseEvent;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Zend\Diactoros\StreamFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

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
            $response = $this->context->get(ResponseInterface::class);
            $this->context->set('return', $exception);
            $dispatcher->dispatch(new BeforeResponseEvent($this->context));
            $response = $response->withStatus($exception->getStatusCode())
                ->withBody((new StreamFactory())->createStream(json_encode($this->context->get('return'))));

            $emitter = new SapiEmitter();
            $emitter->emit($response);
        } else {
            parent::sendPhpResponse($exception);
        }
    }
}
