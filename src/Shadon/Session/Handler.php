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

namespace Shadon\Session;

use Phalcon\Cache\BackendInterface;

/**
 * Phalcon cache Handler.
 *
 * @author hehui<hehui@eelly.net>
 */
class Handler implements \SessionHandlerInterface
{
    /***
     * @var BackendInterface
     */
    protected $backend;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * Handler constructor.
     *
     * @param \Phalcon\Cache\BackendInterface $backend fully initialized backend instance
     * @param array                           $options session handler options
     */
    public function __construct(BackendInterface $backend, array $options = [])
    {
        $this->backend = $backend;
        $this->ttl = (int) ($options['gc_maxlifetime'] ?? ini_get('session.gc_maxlifetime'));
        session_name($options['name'] ?? ini_get('session.name'));
        session_set_cookie_params(
            (int) ($options['cookie_lifetime'] ?? ini_get('session.cookie_lifetime')),
            $options['cookie_path'] ?? ini_get('session.cookie_path'),
            $options['cookie_domain'] ?? ini_get('session.cookie_domain'),
            (bool) ($options['cookie_secure'] ?? ini_get('session.cookie_secure')),
            (bool) ($options['cookie_httponly'] ?? ini_get('session.cookie_httponly'))
        );
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register(): void
    {
        session_set_save_handler($this, true);
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($data = $this->backend->get($sessionId)) {
            return $data;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->backend->save($sessionId, $data, $this->ttl);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->backend->delete($sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // NOOP
        return true;
    }

    /**
     * Returns the underlying backend instance.
     *
     * @return BackendInterface
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
}
