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

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * @author hehui<hehui@eelly.net>
 */
class WebDispatcher extends Dispatcher
{
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
        if ($exception instanceof DispatchException) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $dispatcher->forward([
                        'namespace'  => 'App\\Controller',
                        'controller' => 'error_page',
                        'action'     => 'notFound',
                        'params'     => ['exception' => $exception],
                    ]);

                    return false;
            }
        }
        if ($exception instanceof \LogicException) {
            $dispatcher->forward([
                'namespace'  => 'App\\Controller',
                'controller' => 'error_page',
                'action'     => 'whoops',
                'params'     => ['exception' => $exception],
            ]);
        }
    }
}
