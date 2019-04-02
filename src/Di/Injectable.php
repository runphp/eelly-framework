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
        // attach db profile
        $di->get('eventsManager')->attach('db', function ($event, Mysql $connection): void {
            /* @var \Phalcon\Db\Profiler $profiler */
            $profiler = $this->getDI()->getShared('dbProfiler');
            if ('beforeQuery' === $event->getType()) {
                $sqlStatement = $connection->getSQLStatement();
                $sqlVariables = $connection->getSqlVariables();
                $sqlBindTypes = $connection->getSQLBindTypes();
                $profiler->startProfile($sqlStatement, $sqlVariables, $sqlBindTypes);
            }
            if ('afterQuery' === $event->getType()) {
                $profiler->stopProfile();
                if (100 < $profiler->getNumberTotalStatements()) {
                    $profiler->reset();
                }
            }
        });
        // log slow mysql
        register_shutdown_function(function ($di): void {
            /* @var \Phalcon\Db\Profiler $profiler */
            $profiler = $di->getShared('dbProfiler');
            $totalElapsedSeconds = $profiler->getTotalElapsedSeconds();
            if (0 == $profiler->getNumberTotalStatements() || 5 > $totalElapsedSeconds) {
                return;
            }
            $context = [
                'numberTotalStatements' => $profiler->getNumberTotalStatements(),
                'totalElapsedSeconds'   => $totalElapsedSeconds,
                'profiles'              => [],
            ];
            foreach ($profiler->getProfiles() as $item) {
                $context['profiles'][] = [
                    'sqlStatement'        => $item->getSqlStatement(),
                    'sqlVariables'        => $item->getSqlVariables(),
                    'sqlBindTypes'        => $item->getSqlBindTypes(),
                    'totalElapsedSeconds' => $item->getTotalElapsedSeconds(),
                ];
            }
            /* @var \Monolog\Logger $logger */
            $logger = $di->getShared('errorLogger');
            $logger->info('Slow sql', $context);
        }, $di);
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
