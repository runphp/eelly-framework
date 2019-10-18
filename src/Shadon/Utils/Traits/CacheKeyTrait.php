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

namespace Shadon\Utils\Traits;

trait CacheKeyTrait
{
    /**
     * 缓存key.
     *
     * @param string $class
     * @param string $method
     * @param array  $params
     *
     * @return string
     */
    protected function keyName($class, $method, array $params)
    {
        $keyName = sprintf('%s:%s:%s', $class, $method, $this->createKeyWithArray($params));
        // Add hash tag
        $keyName .= ':{'.md5($keyName).'}';

        return $keyName;
    }

    private function createKeyWithArray(array $parameters)
    {
        $uniqueKey = [];
        foreach ($parameters as $key => $value) {
            if (null === $value) {
                continue;
            }
            if (is_scalar($value)) {
                $uniqueKey[] = $key.':'.$value;
            } elseif (\is_array($value)) {
                $uniqueKey[] = $key.':['.$this->createKeyWithArray($value).']';
            } else {
                throw new \InvalidArgumentException('can not use cache annotation', 500);
            }
        }
        $keyWithArray = implode(',', $uniqueKey);
        if (100 < \strlen($keyWithArray)) {
            $keyWithArray = md5($keyWithArray);
        }

        return $keyWithArray;
    }
}
