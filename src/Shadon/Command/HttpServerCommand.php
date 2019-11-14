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

use Interop\Queue\Context as QueueContext;
use Shadon\Context\ContextInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HttpServerCommand.
 *
 * @author hehui<runphp@qq.com>
 */
class HttpServerCommand extends Command
{
    protected static $defaultName = 'httpserver';

    /**
     * @Inject
     *
     * @var ContextInterface
     */
    private $context;

    /**
     * @Inject
     *
     * @var QueueContext
     */
    private $queueContext;

    protected function configure(): void
    {
        $this
            ->setDescription('http server')
            ->setHelp('http server start');
        $this->addArgument('cmd', InputArgument::REQUIRED, 'http服务器命令[start|stop|reload|status]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
    }
}
