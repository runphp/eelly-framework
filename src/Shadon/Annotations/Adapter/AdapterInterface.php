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

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface extends \Phalcon\Annotations\AdapterInterface
{
    /**
     * delete cache.
     *
     * @param $key
     */
    public function delete($key): void;
}
