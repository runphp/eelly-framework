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

namespace Shadon\Network\Traits;

use Phalcon\DiInterface;
use Shadon\Client\TcpClient;
use Shadon\Exception\RequestException;
use Shadon\Utils\DateTime;
use Swoole\Lock;
use Swoole\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait ServerTrait.
 */
trait ServerTrait
{
    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var Table
     */
    private $moduleMap;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string $moduleName
     *
     * @return TcpClient
     */
    public function getModuleClient(string $moduleName)
    {
        $module = $this->moduleMap->get($moduleName);
        if (false === $module) {
            throw new RequestException(404, 'Module ('.$moduleName.') not found', $this->di->getShared('request'), $this->di->getShared('response'));
        }
        static $mdduleClientMap = [];
        if (isset($mdduleClientMap[$moduleName])) {
            if ($mdduleClientMap[$moduleName]['ip'] == $module['ip']
                && $mdduleClientMap[$moduleName]['port'] == $module['port']) {
                if (!$mdduleClientMap[$moduleName]['client']->isConnected()) {
                    $mdduleClientMap[$moduleName]['client']->connect($module['ip'], $module['port']);
                }

                return $mdduleClientMap[$moduleName]['client'];
            } else {
                // force close
                $mdduleClientMap[$moduleName]['client']->close(true);
                unset($mdduleClientMap[$moduleName]);
            }
        }
        $client = new TcpClient(SWOOLE_TCP | SWOOLE_KEEP);
        $client->connect($module['ip'], $module['port']);
        $mdduleClientMap[$moduleName] = [
            'ip'     => $module['ip'],
            'port'   => $module['port'],
            'client' => $client,
        ];

        return $client;
    }

    /**
     * register module.
     *
     * @param string $module
     * @param string $ip
     * @param int    $port
     */
    public function registerModule(string $module, string $ip, int $port): void
    {
        $record = $this->moduleMap->get($module);
        $created = false == $record ? time() : $record['created'];
        $this->moduleMap->set($module, ['ip' => $ip, 'port' => $port, 'created' => $created, 'updated' => time()]);
        $this->writeln(sprintf('register module(%s) %s:%d', $module, $ip, $port), OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * @return array
     */
    public function getModuleMap()
    {
        $moduleMap = [];
        foreach ($this->moduleMap as $module => $value) {
            $moduleMap[$module] = $value;
        }

        return $moduleMap;
    }

    /**
     * @param string $string
     * @param int    $option
     */
    public function writeln(string $string, $option = 0): void
    {
        $info = sprintf('[%s %d] %s', DateTime::formatTime(), getmypid(), $string);
        $this->lock->lock();
        $this->output->writeln($info, $option);
        $this->lock->unlock();
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return DiInterface
     */
    public function getDi(): DiInterface
    {
        return $this->di;
    }

    /**
     * @param DiInterface $di
     */
    public function setDi(DiInterface $di): void
    {
        $this->di = $di;
    }

    /**
     * @return Table
     */
    private function createModuleMap()
    {
        $moduleMap = new Table(self::MAX_MODULE_MAP_COUNT);
        $moduleMap->column('ip', Table::TYPE_STRING, 15);
        $moduleMap->column('port', Table::TYPE_INT);
        $moduleMap->column('created', Table::TYPE_INT);
        $moduleMap->column('updated', Table::TYPE_INT);
        $moduleMap->create();

        return $moduleMap;
    }
}
