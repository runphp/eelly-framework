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

/**
 * @author hehui<hehui@eelly.net>
 */
class Manager extends TransactionManager
{
    protected $_service = 'dbMaster';
}
