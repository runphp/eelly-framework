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

namespace Shadon\Cache\Backend;

use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\FrontendInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Predis extends Redis
{
    private const FRONTEND_PREFIX = [
        \Phalcon\Cache\Frontend\Data::class     => '_D_',
        \Phalcon\Cache\Frontend\Igbinary::class => '_I_',
    ];

    public function __construct(FrontendInterface $frontend, $options = null)
    {
        $this->_prefix = self::FRONTEND_PREFIX[\get_class($frontend)];
        parent::__construct($frontend, $options);
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Cache\Backend\Redis::_connect()
     */
    public function _connect(): void
    {
        $options = [
            'parameters' => $this->_options['parameters'],
            'options'    => $this->_options['options'],
        ];
        static $redisClients = [];
        $clientKey = md5(serialize($options));
        if (isset($redisClients[$clientKey])) {
            $this->_redis = $redisClients[$clientKey];
        } else {
            $this->_redis = $redisClients[$clientKey] = new PredisResource($options['parameters'], $options['options']);
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Cache\Backend\Redis::save()
     *
     * @param mixed|null $keyName
     * @param mixed|null $content
     * @param mixed|null $lifetime
     * @param mixed      $stopBuffer
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {
        /* @var \Predis\Response\Status $status */
        $status = parent::save($keyName, $content, $lifetime, $stopBuffer);

        return 'OK' == (string) $status;
    }

    /**
     * @return PredisResource
     */
    public function getPredis()
    {
        if (null === $this->_redis) {
            $this->_connect();
        }

        return $this->_redis;
    }
}
