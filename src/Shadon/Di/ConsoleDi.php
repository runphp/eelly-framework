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
use Phalcon\Di\Service;
use Shadon\Dispatcher\EventDispatcher;
use Shadon\Dispatcher\ServiceDispatcher;
use Shadon\Http\PhalconServiceResponse;
use Shadon\Http\SwoolePhalconRequest;
use Shadon\Logger\Handler\EellyapiHandler;
use Shadon\Mvc\Collection\Manager as CollectionManager;
use Shadon\Mvc\Model\Manager as ModelsManager;
use Shadon\Mvc\Model\Transaction\Manager as TransactionManager;
use Shadon\Router\ServiceRouter;

/**
 * @author hehui<hehui@eelly.net>
 */
class ConsoleDi extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();
        $this->_services['collectionManager'] = new Service('collectionManager', CollectionManager::class, true);
        $this->_services['dispatcher'] = new Service('dispatcher', ServiceDispatcher::class, true);
        $this->_services['errorViewHandler'] = new Service('errorViewHandler', function () {
            return new StreamHandler('php://stderr');
        }, true);
        $this->_services['eventDispatcher'] = new Service('eventDispatcher', EventDispatcher::class, true);
        $this->_services['logger'] = new Service('logger', function () {
            $channel = APP['appname'].'.'.APP['env'];
            $logger = new Logger($channel);
            $config = $this->getShared('config');
            $stream = realpath($config['logPath']).'/app.'.date('Ymd').'.txt';
            $logger->pushHandler(new StreamHandler($stream));

            return $logger;
        });
        $this->_services['errorLogger'] = new Service('errorLogger', function () {
            $logger = clone $this->get('logger');
            $logger->pushHandler(new EellyapiHandler());
            $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

            return $logger;
        }, true);
        $this->_services['modelsManager'] = new Service('modelsManager', ModelsManager::class, true);
        $this->_services['request'] = new Service('request', SwoolePhalconRequest::class, true);
        $this->_services['response'] = new Service('response', PhalconServiceResponse::class, true);
        $this->_services['router'] = new Service('router', ServiceRouter::class, true);
        $this->_services['transactionManager'] = new Service('transactionManager', TransactionManager::class, true);
    }
}
