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

namespace Shadon\Mvc\Model\MetaData;

use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Mvc\Model\MetaData\Redis;
use Shadon\Cache\Backend\Predis as BackendRedis;

/**
 * class Predis.
 *
 * ```
 * use Shadon\Mvc\Model\MetaData\Predis;
 *
 * $modelMetaData = new Predis([
 *  'parameters' => [
 *      'tcp://172.18.107.120:7000',
 *      'tcp://172.18.107.120:7001',
 *      'tcp://172.18.107.120:7002',
 *      'tcp://172.18.107.120:7003',
 *      'tcp://172.18.107.120:7004',
 *      'tcp://172.18.107.120:7005',
 *  ],
 *  'options' => ['cluster' => 'redis'],
 *  'lifetime' => 172800,
 *  'statsKey' => '_PHCR_MODEL_METADATA_STATS',
 * ]);
 * ```
 *
 * @author hehui<hehui@eelly.net>
 */
class Predis extends Redis
{
    public function __construct($options = null)
    {
        if (isset($options['lifetime'])) {
            $this->_ttl = $options['lifetime'];
        }
        $this->_redis = new BackendRedis(new FrontendData(['lifetime' => $this->_ttl]), $options);
    }
}
