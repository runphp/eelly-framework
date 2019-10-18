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

namespace Shadon\Mvc\User\Traits;

/**
 * 结果集转换.
 *
 * @author wangjiang<wangjiang@eelly.net>
 *
 * @since 2017-08-18
 */
trait ResultHydratorTrait
{
    /**
     * 结果集字段映射(支持多维数组,支持转换为对象).
     *
     * Example:
     * $data = [
     *     'a' => '1',
     *     'b' => '2',
     *     'c' => '3',
     * ];
     * $columnMap = [
     *     'a' => ['column' => 'AAA', 'type' => 'int'],
     *     'b' => ['column' => 'BBB', 'type' => 'bool'],
     *     'c' => ['column' => 'CCC',]
     * ];
     * $this->getResultByColumnMap($data, $columnMap);
     * Result:
     * [
     *     'AAA' => 1,
     *     'BBB' => true,
     *     'CCC' => '3',
     * ]
     *
     * @param array  $data          需转换的数据
     * @param array  $columnMap     字段映射关系
     * @param string $hydrationMode 转换数据模式 array或类名
     *
     * @return array
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since 2017-08-14
     */
    public function getResultByColumnMap(array $data, array $columnMap, string $hydrationMode = 'array')
    {
        if (empty($data) || empty($columnMap)) {
            return [];
        }

        if ('array' == $hydrationMode) {
            $hydration = [];
        } else {
            if (!class_exists($hydrationMode)) {
                throw new \Phalcon\Exception($hydrationMode.'类型加载失败');
            }
            $hydration = new $hydrationMode();
        }

        if (\count($data) == \count($data, COUNT_RECURSIVE)) {
            foreach ($data as $key => $val) {
                isset($columnMap[$key]['type']) && $this->convertValueType($val, $columnMap[$key]['type']);
                $key = $columnMap[$key]['column'] ?? $key;

                if ('array' == $hydrationMode) {
                    $hydration[$key] = $val;
                } else {
                    $hydration->$key = $val;
                }
            }

            return $hydration;
        } else {
            $result = [];
            foreach ($data as $recursiveData) {
                $result[] = $this->getResultByColumnMap($recursiveData, $columnMap, $hydrationMode);
            }
        }

        return $result;
    }

    /**
     * 转换值的类型.
     *
     * @param mixed  $value 需转换的值
     * @param string $type  转换的类型
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since 2017-08-16
     */
    protected function convertValueType(&$value, string $type): void
    {
        ('date' === $type && $value = date('Y-m-d H:i:s', (int) $value)) || settype($value, $type);
    }
}
