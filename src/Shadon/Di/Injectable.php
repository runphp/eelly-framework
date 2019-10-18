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

use Phalcon\Db\Profiler;
use Phalcon\Di\Injectable as DiInjectable;
use Shadon\Db\Adapter\Pdo\Factory as PdoFactory;
use Shadon\Db\Adapter\Pdo\Mysql;
use Shadon\Queue\Adapter\AMQPFactory;

/**
 * @author hehui<hehui@eelly.net>
 */
abstract class Injectable extends DiInjectable implements InjectionAwareInterface
{
    /**
     * Register db service.
     */
    public function registerDbService(): void
    {
        $di = $this->getDI();
        // db profiler service
        $di->setShared('dbProfiler', function () {
            return new Profiler();
        });
        // mysql master connection service
        $di->setShared('dbMaster', function () {
            $options = $this->getModuleConfig()->mysql->master->toArray();
            $options['adapter'] = 'Mysql';
            $connection = PdoFactory::load($options);
            $connection->setEventsManager($this->get('eventsManager'));

            return $connection;
        });
        // mysql slave connection service
        $di->setShared('dbSlave', function () {
            $config = $this->getModuleConfig()->mysql->slave->toArray();
            shuffle($config);
            $options = current($config);
            $masterOptions = $this->getModuleConfig()->mysql->master->toArray();
            if ($options == $masterOptions) {
                return $this->getShared('dbMaster');
            }
            $options['adapter'] = 'Mysql';
            $connection = PdoFactory::load($options);
            $connection->setEventsManager($this->get('eventsManager'));

            return $connection;
        });
        // register modelsMetadata service
        $di->setShared('modelsMetadata', function () {
            $config = $this->getModuleConfig()->mysql->metaData->toArray();

            return $this->get($config['adapter'], [
                $config['options'][$config['adapter']],
            ]);
        });
    }

    /**
     * Register queue service.
     */
    public function registerQueueService(): void
    {
        $this->getDI()->set('queueFactory', function () {
            $connectionOptions = $this->getModuleConfig()->amqp->toArray();

            return new AMQPFactory($connectionOptions);
        });
    }
}
