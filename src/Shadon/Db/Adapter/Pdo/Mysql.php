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

namespace Shadon\Db\Adapter\Pdo;

use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Shadon\Application\ApplicationConst;

class Mysql extends PdoMysql
{
    private const MYSQL_GONE_AWAY_EXCEPTION = 'MySQL server has gone away';

    /**
     * {@inheritdoc}
     */
    public function query($sqlStatement, $bindParams = null, $bindTypes = null)
    {
        try {
            return parent::query($sqlStatement, $bindParams, $bindTypes);
        } catch (\Exception $e) {
            if ($this->isGoneAwayException($e) || null === $this->getInternalHandler()) {
                $this->reconnect();

                return parent::query($sqlStatement, $bindParams, $bindTypes);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sqlStatement, $bindParams = null, $bindTypes = null): bool
    {
        try {
            return parent::execute($sqlStatement, $bindParams, $bindTypes);
        } catch (\Exception $e) {
            if ($this->isGoneAwayException($e) || null === $this->getInternalHandler()) {
                $this->reconnect();

                return parent::execute($sqlStatement, $bindParams, $bindTypes);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $descriptor = null): bool
    {
        parent::connect($descriptor);
        $sql = sprintf('/* %s %s */', ApplicationConst::getRequestAction(), APP['requestId']);
        $this->_pdo->exec($sql);

        return true;
    }

    public function reconnect(): void
    {
        $this->close();
        $this->connect();
    }

    /**
     * @return \Pdo
     */
    public function getPdo()
    {
        return $this->_pdo;
    }

    private function isGoneAwayException(\Exception $exception)
    {
        return false !== stripos($exception->getMessage(), self::MYSQL_GONE_AWAY_EXCEPTION);
    }
}
