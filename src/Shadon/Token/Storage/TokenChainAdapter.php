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

namespace  Shadon\Token\Storage;

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

    public function saveToken(string $tokenId, User $data): void
    {
        $cacheKey = $this->tokenKey($data->uid);
        $cacheItem = $this->getItem($cacheKey);
        $now = time();
        if (!$cacheItem->isHit()) {
            $value = [
                'tokens' => [],
            ];
        } else {
            $value = $cacheItem->get();
            // 旧token失效 最多保留10个token
            $minTime = time();
            foreach ($value['tokens'] as $k => $v) {
                if (!$value['tokens'][$k]['revoked']) {
                    $value['tokens'][$k]['revoked'] = true;
                    $value['tokens'][$k]['updated'] = $now;
                }
                if ($value['tokens'][$k]['updated'] < $minTime) {
                    $minTime = $value['tokens'][$k]['updated'];
                    $minKey = $k;
                }
            }
            if (self::MAX_TOKENS <= \count($value['tokens'])) {
                unset($value['tokens'][$minKey]);
            }
        }

        // 增加新token
        $value['tokens'][$tokenId] = [
            'revoked' => false,
            'created' => $now,
            'updated' => $now,
        ];
        $value['data'] = $data->getArrayCopy();

        $cacheItem->set($value);
        $this->save($cacheItem);
    }

    public function fetchToken(string $tokenId, int $uid): DataInterface
    {
        $cacheKey = $this->tokenKey($uid);
        $cacheItem = $this->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            // TODO 恢复数据
            throw new UnsupportedException('token data lost');
        } else {
            $value = $cacheItem->get();
            if (!isset($value['tokens'][$tokenId])) {
                // 已删除
                throw new UnauthorizedException(sprintf('not found token `%s`', $tokenId));
            } elseif ($value['tokens'][$tokenId]['revoked']) {
                // 已失效
                throw new UnauthorizedException(sprintf('token `%s` was revoked at %s', $tokenId, date('Y-m-d H:i:s', $value['tokens'][$tokenId]['updated'])));
            } else {
                // 刷新时间
                $value['tokens'][$tokenId]['updated'] = time();
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
