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

namespace Shadon\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Zend\Expressive\Swoole\Command\StartCommand;
use Zend\Expressive\Swoole\PidManager;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\MiddlewarePipeInterface;

/**
 * Class HttpServerCommand.
 *
 * @author hehui<runphp@qq.com>
 */
class HttpServerCommandFactory
{
    public static function createFactories(ContainerInterface $container): array
    {
        return [
            'httpserver:start' => function () use ($container) {
                return self::createStartCommand($container);
            },
        ];
    }

    private static function createStartCommand(ContainerInterface $container): Command
    {
        // $container->set(PidManager::class, new PidManager('var/httpserver.pid'));
        // $container->set(MiddlewarePipeInterface::class, new MiddlewarePipe());
        // TODO add definitions
        return new StartCommand($container, 'httpserver:start');
    }
}
