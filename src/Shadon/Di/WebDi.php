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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Phalcon\Di\Service;
use Shadon\Logger\Handler\EellyapiHandler;

/**
 * Class WebDi.
 */
class WebDi extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();
        $this->_services['annotations'] = new Service('annotations', 'Phalcon\\Annotations\\Adapter\\Memory', true);
        $this->_services['dispatcher'] = new Service('dispatcher', 'Shadon\\Dispatcher\\WebDispatcher', true);
        $this->_services['escaper'] = new Service('escaper', 'Phalcon\\Escaper', true);
        $this->_services['filter'] = new Service('filter', 'Phalcon\\Filter', true);
        $this->_services['flash'] = new Service('flash', 'Shadon\\Flash\\Direct', true);
        $this->_services['logger'] = new Service('logger', function () {
            $logger = new Logger(APP['appname'].'.'.APP['env']);
            $config = $this->getShared('config');
            $stream = realpath($config['logPath']).'/app.'.date('Ymd').'.txt';
            $logger->pushHandler(new StreamHandler($stream));

            return $logger;
        });
        $this->_services['errorLogger'] = new Service('errorLogger', function () {
            $logger = clone $this->get('logger');
            $logger->pushHandler(new EellyapiHandler());
            $logger->pushProcessor(new WebProcessor(null, ['server', 'url', 'ip']));

            return $logger;
        }, true);
        $this->_services['response'] = new Service('response', 'Phalcon\\Http\\Response', true);
        $this->_services['request'] = new Service('request', 'Phalcon\\Http\\Request', true);
        $this->_services['security'] = new Service('security', 'Phalcon\\Security', true);
        $this->_services['url'] = new Service('url', 'Phalcon\\Mvc\\Url', true);
        $this->_services['view'] = new Service('view', 'Shadon\\Mvc\\View', true);
    }
}
