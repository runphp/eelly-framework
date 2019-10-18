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

namespace Shadon\Console;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Events\ManagerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * @author hehui<hehui@eelly.net>
 */
class Application extends ConsoleApplication implements InjectionAwareInterface
{
    private const LOGO = '                                          _
                                         / )
                                    .--.; |    _...,-"""-,
                     .-""-.-""""-. /   _`\'-._.\'   /`      \
                    /\'     \      \|  (/\'-._/     )        ;
            .-""""-;       (       \'--\' /-\'    _           |
          .\'       |        ;    e     /       a  ,       ;
         /          \       |      __.\'`-.__,    ;       /
        /            `._     ;    .-\'     `--.,__.\    /`
       //|              \     \,-\'                /\_.\'
      // |               `;.___>              /,-\'.
    /`|  /                |`\      _..---\    |    \
    |/  /     _,.-----\   |  \   /`|      |   |\    \
       /    .;   |    |   |   \ /  |      |   | \    )
      |    / |   \    /   |\..\' \   \     |   \  \..\'
       \../  \.../    \.../ \.../---\'     \.../
-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==-==
';
    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * @var ManagerInterface
     */
    protected $eventsManager;

    /**
     * @var array
     */
    protected $modules = [];

    /**
     * add modules commands.
     *
     * @return self
     */
    public function registerModulesCommands(): self
    {
        $classMap = [];
        foreach ($this->di->getShared('config')->moduleList as $value) {
            $classMap[ucfirst($value).'\\Module'] = 'src/'.ucfirst($value).'/Module.php';
        }
        /* @var \Composer\Autoload\ClassLoader $loader */
        $loader = $this->di->get('loader');
        $loader->addClassMap($classMap);
        foreach (array_keys($classMap) as $class) {
            $this->di->getShared($class)->registerCommands($this);
        }
        $loader->register(true);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Di\InjectionAwareInterface::setDI()
     */
    public function setDI(DiInterface $di): void
    {
        $this->di = $di;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Di\InjectionAwareInterface::getDI()
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return self::LOGO.parent::getHelp();
    }
}
