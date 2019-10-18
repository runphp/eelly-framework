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

namespace Shadon\Session\Adapter;

use Phalcon\Session\Adapter;
use Shadon\Cache\Backend\Predis as CachePredis;

class Predis extends Adapter
{
    protected $_redis;

    protected $_lifetime = 1440;

    public function __construct(array $options = [])
    {
        if ($lifetime = $options['lifetime'] ?? '') {
            $this->_lifetime = $lifetime;
        }

        $cacheOptions['parameters'] = $options['parameters'] ?? ['tcp://127.0.0.1:6379'];
        $cacheOptions['options'] = [
            'prefix' => $options['prefix'] ?? '',
        ];
        isset($options['cluster']) && $cacheOptions['options']['cluster'] = $options['cluster'];
        $this->_redis = new CachePredis(new \Phalcon\Cache\Frontend\Igbinary([
            'lifetime' => $this->_lifetime,
        ]), $cacheOptions);
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );

        parent::__construct($options);
    }

    public function getRedis()
    {
        return $this->_redis;
    }

    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function open(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string
    {
        return (string) $this->_redis->get($sessionId, $this->_lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $sessionId, string $data): bool
    {
        return $this->_redis->save($sessionId, $data, $this->_lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId = null): bool
    {
        if (null === $sessionId) {
            $id = $this->getId();
        } else {
            $id = $sessionId;
        }

        $this->removeSessionData();

        return $this->_redis->exists($id) ? $this->_redis->delete($id) : true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(): bool
    {
        return true;
    }
}
