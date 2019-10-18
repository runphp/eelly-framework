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

use Predis\Client as PredisClient;

class PredisResource extends PredisClient
{
    /**
     * @param string $key
     *
     * @return int
     */
    public function delete($key)
    {
        return $this->del($key);
    }

    /**
     * @param string $key
     * @param int    $lifetime
     *
     * @return int
     */
    public function settimeout($key, $lifetime)
    {
        return $this->expire($key, $lifetime);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return (bool) parent::exists($key);
    }
}
