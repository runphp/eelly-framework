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

namespace Shadon\Annotations\Adapter;

use Phalcon\Cache\Frontend\Data as FrontendData;
use Shadon\Cache\Backend\Predis as BackendRedis;

/**
 * Class Predis.
 *
 *
 * ```
 * use Shadon\Annotations\Adapter\Predis;
 *
 * $annotations = new Predis([
 *  'parameters' => [
 *      'tcp://172.18.107.120:7000',
 *      'tcp://172.18.107.120:7001',
 *      'tcp://172.18.107.120:7002',
 *      'tcp://172.18.107.120:7003',
 *      'tcp://172.18.107.120:7004',
 *      'tcp://172.18.107.120:7005',
 *  ],
 *  'options' => ['cluster' => 'redis'],
 *  'statsKey' => '_PHCR_ANNOTATIONS_STATS',
 * ]);
 * ```
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年5月18日
 *
 * @version   1.0
 */
class Predis extends AbstractAdapter
{
    /**
     * @var BackendRedis
     */
    protected $redis;

    /**
     * {@inheritdoc}
     *
     * @param array $options Options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->redis = new BackendRedis(new FrontendData([
            'lifetime' => $this->options['lifetime'],
        ]), $this->options);
    }

    /**
     * {@inheritdoc}
     *
     * @return BackendRedis
     */
    protected function getCacheBackend()
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     */
    protected function prepareKey($key)
    {
        return (string) $key;
    }
}
