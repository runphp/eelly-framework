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

use Shadon\Exception\UnauthorizedException;
use Shadon\Exception\UnsupportedException;
use Shadon\Token\Data\DataInterface;
use Shadon\Token\Data\User;

/**
 * Class TokenChainAdapter.
 *
 * @author hehui<runphp@qq.com>
 */
class TokenChainAdapter extends \Symfony\Component\Cache\Adapter\ChainAdapter implements \Shadon\Token\Storage\StorageInterface
{
    /**
     * max tokens.
     *
     * @var int
     */
    private const MAX_TOKENS = 10;

    private const TOKENS = 'tokens';

    private const REVOKED = 'revoked';

    private const UPDATED = 'updated';

    private const CREATED = 'created';

    public function saveToken(string $tokenId, User $data): void
    {
        $cacheKey = $this->tokenKey($data->uid);
        $cacheItem = $this->getItem($cacheKey);
        $now = time();
        if (!$cacheItem->isHit()) {
            $value = [
                self::TOKENS => [],
            ];
        } else {
            $value = $cacheItem->get();
            // 旧token失效 最多保留10个token
            $minTime = time();
            foreach ($value[self::TOKENS] as $k => $v) {
                if (!$value[self::TOKENS][$k][self::REVOKED]) {
                    $value[self::TOKENS][$k][self::REVOKED] = true;
                    $value[self::TOKENS][$k][self::UPDATED] = $now;
                }
                if ($value[self::TOKENS][$k][self::UPDATED] < $minTime) {
                    $minTime = $value[self::TOKENS][$k][self::UPDATED];
                    $minKey = $k;
                }
            }
            if (self::MAX_TOKENS <= \count($value[self::TOKENS])) {
                unset($value[self::TOKENS][$minKey]);
            }
        }

        // 增加新token
        $value[self::TOKENS][$tokenId] = [
            self::REVOKED => false,
            self::CREATED => $now,
            self::UPDATED => $now,
        ];
        $value['data'] = $data->getArrayCopy();

        $cacheItem->set($value);
        $this->save($cacheItem);
    }

    public function fetchToken(string $tokenId, string $uid): DataInterface
    {
        $cacheKey = $this->tokenKey($uid);
        $cacheItem = $this->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            // TODO 恢复数据
            throw new UnsupportedException('token data lost');
        } else {
            $value = $cacheItem->get();
            if (!isset($value[self::TOKENS][$tokenId])) {
                // 已删除
                throw new UnauthorizedException(sprintf('not found token `%s`', $tokenId));
            } elseif ($value[self::TOKENS][$tokenId][self::REVOKED]) {
                // 已失效
                throw new UnauthorizedException(sprintf('token `%s` was revoked at %s', $tokenId, date('Y-m-d H:i:s', $value[self::TOKENS][$tokenId][self::UPDATED])));
            } else {
                // 刷新时间
                $value[self::TOKENS][$tokenId][self::UPDATED] = time();
                $cacheItem->set($value);
                $this->save($cacheItem);
            }
        }

        return new User($value['data']);
    }

    private function tokenKey(string $uid): string
    {
        return sprintf('token_%s_%s', $uid, md5($uid));
    }
}
