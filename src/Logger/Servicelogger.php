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

namespace Eelly\Logger;

use Eelly\Application\ApplicationConst;
use Monolog\Logger;
use Phalcon\Di\InjectionAwareInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Servicelogger extends Logger implements InjectionAwareInterface
{
    private $dependencyInjector;

    public function __construct()
    {
        parent::__construct(ApplicationConst::$env);
    }

    public function afterServiceResolve(): void
    {
        $di = $this->getDI();
        $config = $di->getConfig();
        /**
         * @var \Phalcon\Dispatcher
         */
        $dispatcher = $di->getDispatcher();
        $this->appendName($dispatcher->getModuleName());
        $this->pushHandler(new \Monolog\Handler\StreamHandler($config['logPath'].'/app.'.date('Ymd').'.txt'));
    }

    /**
     * append logger name.
     *
     * @param string $name
     */
    public function appendName(?string $name): void
    {
        if (!empty($name)) {
            $this->name .= '.'.$name;
        }
    }

    /**
     * Sets the dependency injector.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector): void
    {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector.
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->dependencyInjector;
    }
}
