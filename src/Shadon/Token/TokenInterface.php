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

namespace Shadon\Token;

use Shadon\Token\Data\DataInterface;

/**
 * Interface TokenInterface.
 *
 * @author hehui<runphp@qq.com>
 */
interface TokenInterface
{
    public function setToken(?string $tokenId = null, DataInterface $data): DataInterface;

    public function getToken(?string $tokenId = null): DataInterface;
}
