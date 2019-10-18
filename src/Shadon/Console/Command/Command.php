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

namespace Shadon\Console\Command;

use Phalcon\Events\EventsAwareInterface;
use Shadon\Di\InjectionAwareInterface;
use Shadon\Di\Traits\InjectableTrait;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Command extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::initialize()
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        /**
         * @var \Shadon\Mvc\AbstractModule
         */
        $moduleObject = $this->di->getShared(substr(static::class, 0, strpos(static::class, '\\', 1)).'\\Module');
        /*
         * 'registerAutoloaders' and 'registerServices' are automatically called
         */
        $moduleObject->registerAutoloaders($this->di);
        $moduleObject->registerServices($this->di);
    }
}
