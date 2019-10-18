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

namespace Shadon\Utils;

/**
 * Arrays工具类.
 */
class Arrays
{
    /**
     * 二维数组 排序
     * Arrays::multisort($data, 'order', SORT_ASC, 'id', SORT_DESC);.
     *
     * @param mixed ...$args
     *
     * @return array
     *
     * @author SpiritTeam
     *
     * @since  2015年6月6日
     */
    public static function multisort()
    {
        $args = \func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (\is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        \call_user_func_array('array_multisort', $args);

        return $data;
    }

    /**
     * 切换数组的下标.
     *
     * @desc    使用示例
     * 示例一：
     *  参数：
     *      $array      = array(
     *          0 => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          1 => ['id' => 16, 'name' => 'b', 'age' => 13],
     *          2 => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      );
     *      $field      = 'name'
     *      $multiple   = false
     *  结果：
     *      array(
     *          'a' => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          'b' => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      );
     *
     * 示例二：
     *  参数：
     *      $array      = array(
     *          0 => ['id' => 12, 'name' => 'a', 'age' => 12],
     *          1 => ['id' => 16, 'name' => 'b', 'age' => 13],
     *          2 => ['id' => 17, 'name' => 'b', 'age' => 14],
     *      );
     *      $field      = 'name'
     *      $multiple   = false
     *  结果：
     *      $array = array(
     *          'a' => [['id' => 12, 'name' => 'a', 'age' => 12]],
     *          'b' => [['id' => 16, 'name' => 'b', 'age' => 13], ['id' => 17, 'name' => 'b', 'age' => 14]],
     *      );
     *
     * @param array  $array    需要操作的数组
     * @param string $field    需要切换的字段 'name'
     * @param string $multiple 多维数组
     *
     * @return array
     *
     * @author  Heyanwen<heyanwen@eelly.net>
     *
     * @since   2016-9-21
     */
    public static function switchArrayKey(array $array, $field, $multiple = false)
    {
        // 参数校验
        if (empty($array) || empty($field)) {
            return [];
        }

        // 替换下标
        $result = [];
        $fields = \is_array($field) ? $field : [$field];
        foreach ($array as $k => $v) {
            $keys = [];
            foreach ($fields as $key) {
                $keys[] = !isset($v[$key]) ? '' : $v[$key];
            }
            $keys = implode('_', $keys);
            $multiple ? $result[$keys][] = $v : $result[$keys] = $v;
        }

        return $result;
    }

    /**
     * 数组降维.
     *
     *@desc
     * 示例一
     * 参数： $arr = [
     *      [1],
     *      [2],
     *      [2],
     *      [3],
     * ]
     *      $unique = 0,
     * 结果：$result = [1, 2, 2, 3]
     *
     * 示例二：
     * 参数： $arr = [
     *      [1],
     *      [2],
     *      [2],
     *      [3],
     * ]
     *      $unique = 1,
     * 结果：$result = [1, 2, 3]
     *
     * @param array $arr    需处理的数组
     * @param int   $unique 是否去重
     *
     * @return array
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since  2017年5月22日
     */
    public static function reduceDimension(array $arr, $unique = 1)
    {
        $result = [];
        foreach ($arr as $val) {
            if (\is_array($val)) {
                $result = array_merge($result, self::reduceDimension($val));
            } else {
                $result[] = $val;
            }
        }

        return 1 === $unique ? array_unique($result) : $result;
    }
}
