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

namespace Shadon\Events\Listener;

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Shadon\Utils\Traits\CacheKeyTrait;

/**
 * cache annotation listener.
 *
 * @author hehui<hehui@eelly.net>
 */
class CacheAnnotationListener extends AbstractListener
{
    use CacheKeyTrait;

    /**
     * 注解名称.
     */
    private const ANNOTATIONS_NAME = 'Cache';

    /**
     * 默认缓存时间.
     */
    private const DEFAULT_LIFETIME = 300;

    /**
     * 缓存命中.
     *
     * @var string
     */
    private $hited = false;

    /**
     * @var string
     */
    private $keyName;

    /**
     * @var \Phalcon\Annotations\Collection
     */
    private $annotationsColletion;

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     *
     * @return bool
     */
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        // Parse the annotations in the method currently executed
        $annotations = $this->annotations->getMethod(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );
        $this->annotationsColletion = $annotations;
        if ($annotations->has(self::ANNOTATIONS_NAME)) {
            $this->keyName = $this->keyName($dispatcher->getControllerClass(), $dispatcher->getActiveMethod(), $dispatcher->getParams());
            if ('no-cache' == $this->request->getHeader('Cache-Control')) {
                return true;
            }
            $this->hited = $this->cache->exists($this->keyName);
            if ($this->hited) {
                $returnValue = $this->cache->get($this->keyName);
                $dispatcher->setReturnedValue($returnValue);
                if ($event->isCancelable()) {
                    $event->stop();
                }

                return false;
            }
        }
    }

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     */
    public function afterDispatchLoop(Event $event, Dispatcher $dispatcher): void
    {
        if (false === $this->hited && \is_object($this->annotationsColletion) && $this->annotationsColletion->has(self::ANNOTATIONS_NAME)) {
            $annotation = $this->annotationsColletion->get(self::ANNOTATIONS_NAME);
            $lifetime = $annotation->getNamedParameter('lifetime') ?? self::DEFAULT_LIFETIME;
            $lifetime = self::DEFAULT_LIFETIME < $lifetime ? $lifetime : self::DEFAULT_LIFETIME;
            $returnValue = $dispatcher->getReturnedValue();
            $this->cache->save($this->keyName, $returnValue, $lifetime);
        }
    }
}
