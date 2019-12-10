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

namespace Shadon\Mvc\Model\Transaction;

use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Mvc\Model\TransactionInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Manager extends TransactionManager
{
    protected $_service = 'dbMaster';

    public function get($autoBegin = true): TransactionInterface
    {
        try {
            $tx = parent::get($autoBegin);
        } catch (\Exception $e) {
            /* @var \Shadon\Db\Adapter\Pdo\Mysql $pdo */
            $pdo = $this->getDI()->getShared($this->_service);
            if ($pdo->isGoneAwayException($e) || null === $pdo->getInternalHandler()) {
                $pdo->reconnect();

                return parent::get($autoBegin);
            } else {
                throw $e;
            }
        }

        return $tx;
    }
}
