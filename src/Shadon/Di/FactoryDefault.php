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

namespace Shadon\Di;

use Phalcon\Di\Service;
use Phalcon\DiInterface as Di;
use Phalcon\Events\Event;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager as EventsManager;

abstract class FactoryDefault extends \Phalcon\Di
{
    public function __construct()
    {
        parent::__construct();
        $eventsManager = new  EventsManager();
        $eventsManager->attach('di:afterServiceResolve', function (Event $event, Di $di, array $service): void {
            if ($service['instance'] instanceof EventsAwareInterface) {
                $service['instance']->setEventsManager($di->getEventsManager());
            }
            if (method_exists($service['instance'], 'afterServiceResolve')) {
                $service['instance']->afterServiceResolve();
            }
        });
        $this->setInternalEventsManager($eventsManager);
        $this->_services = [
            'eventsManager' => new Service('eventsManager', $eventsManager, true),
        ];
    }
}
