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

namespace Shadon\Token\Storage;

use Shadon\Token\Data\DataInterface;
use Shadon\Token\Data\User;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\ResettableInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Interface StorageInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface StorageInterface extends AdapterInterface, CacheInterface, PruneableInterface, ResettableInterface
{
    public function saveToken(string $tokenId, User $data): void;

    public function fetchToken(string $tokenId, string $uid): DataInterface;
}
