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
use NunoMaduro\Collision\Adapters\Laravel\Inspector;
use NunoMaduro\Collision\Handler;
use Shadon\Context\ContextInterface;
use Shadon\Events\BeforeResponseEvent;
use function Shadon\Helper\isCli;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Zend\Diactoros\Response\JsonResponse;
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
        if (!$exception instanceof FlattenException) {
            $exception = \Shadon\Exception\FlattenException::create($exception);
        }
        if (\is_object($this->context)) {
            $dispatcher = $this->context->get(Dispatcher::class);
            $this->context->set('return', $exception);
            $dispatcher->dispatch(new BeforeResponseEvent($this->context));
            if (isCli()) {
                // TODO when cli server
                $handler = new Handler();
                $handler->setInspector(new Inspector($exception));
                $handler->handle();
            } else {
                $response = new JsonResponse($this->context->get('return'), $exception->getStatusCode());
                $emitter = new SapiEmitter();
                $emitter->emit($response);
            }
        } else {
            parent::sendPhpResponse($exception);
        }
    }
}
