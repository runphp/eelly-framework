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

namespace Shadon\Di\Traits;

trait InjectableTrait
{
    /**
     * Dependency Injector.
     *
     * @var \Phalcon\DiInterface
     */
    protected $di;

    /**
     * Events Manager.
     *
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $eventsManager;

    /**
     * Magic method __get.
     */
    public function __get(string $propertyName)
    {
        if ($this->di->has($propertyName)) {
            return $this->$propertyName = $this->di->getShared($propertyName);
        }

        if ('di' == $propertyName) {
            return $this->di;
        }
        trigger_error('Access to undefined property '.$propertyName);
    }

    /**
     * Sets the dependency injector.
     *
     * @param \Phalcon\DiInterface $di
     */
    public function setDI(\Phalcon\DiInterface $di): void
    {
        $this->di = $di;
    }

    /**
     * Returns the internal dependency injector.
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * Sets the event manager.
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager(\Phalcon\Events\ManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager.
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getEventsManager()
    {
        $this->eventsManager;
    }
}
