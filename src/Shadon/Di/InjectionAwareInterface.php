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

use Phalcon\Di\InjectionAwareInterface as DiInjectionAwareInterface;

/**
 * @property \Phalcon\Cache\Backend|\Shadon\Cache\Backend\Predis                   $cache
 * @property \Phalcon\Mvc\Application                                              $application
 * @property \Phalcon\Config                                                       $config          系统配置
 * @property \Phalcon\Db\Profiler                                                  $dbProfiler
 * @property \Shadon\Dispatcher\EventDispatcher                                    $eventDispatcher
 * @property \Phalcon\Events\Manager                                               $eventsManager
 * @property \Phalcon\Loader                                                       $loader
 * @property \Psr\Log\LoggerInterface                                              $logger          日志对象
 * @property \Psr\Log\LoggerInterface                                              $errorLogger     错误日志对象
 * @property \Phalcon\Config                                                       $moduleConfig    模块配置
 * @property \Shadon\Queue\Adapter\AMQPFactory|\Shadon\Queue\QueueFactoryInterface $queueFactory
 * @property \Shadon\Network\HttpServer|\Shadon\Network\TcpServer                  $server
 *
 * @author hehui<hehui@eelly.net>
 */
interface InjectionAwareInterface extends DiInjectionAwareInterface
{
}
