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

use Phalcon\Annotations\Adapter;

/**
 * @author    hehui<hehui@eelly.net>
 */
abstract class AbstractAdapter extends Adapter implements AdapterInterface
{
    /**
     * Default option for cache lifetime.
     *
     * @var array
     */
    protected static $defaultLifetime = 8600;

    /**
     * Default option for prefix.
     *
     * @var string
     */
    protected static $defaultPrefix = '_D_';

    /**
     * Backend's options.
     *
     * @var array
     */
    protected $options;

    /**
     * Class constructor.
     *
     * @param array|null $options
     *
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __construct($options = null)
    {
        if (!\is_array($options) || !isset($options['lifetime'])) {
            $options['lifetime'] = self::$defaultLifetime;
        }
        if (!\is_array($options) || !isset($options['prefix'])) {
            $options['prefix'] = self::$defaultPrefix;
        }
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return array
     */
    public function read($key)
    {
        return $this->getCacheBackend()->get($this->prepareKey($key), $this->options['lifetime']);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data): void
    {
        $this->getCacheBackend()->save($this->prepareKey($key), $data, $this->options['lifetime']);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     */
    public function delete($key): void
    {
        $this->getCacheBackend()->delete($this->prepareKey($key));
    }

    /**
     * Returns the key with a prefix or other changes.
     *
     * @param string $key
     *
     * @return string
     */
    abstract protected function prepareKey($key);

    /**
     * Returns cache backend instance.
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    abstract protected function getCacheBackend();
}
