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

namespace Shadon\Mvc\Model\Query;

use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

/**
 * @author Administrator
 *
 * @method \Shadon\Mvc\Model\Query\Builder distinct($distinct)
 * @method \Shadon\Mvc\Model\Query\Builder columns($columns)
 * @method \Shadon\Mvc\Model\Query\Builder join($model, $conditions = null, $alias = null, $type = null)
 * @method \Shadon\Mvc\Model\Query\Builder innerJoin($model, $conditions = null, $alias = null)
 * @method \Shadon\Mvc\Model\Query\Builder leftJoin($model, $conditions = null, $alias = null)
 * @method \Shadon\Mvc\Model\Query\Builder rightJoin($model, $conditions = null, $alias = null)
 * @method \Shadon\Mvc\Model\Query\Builder where($conditions, $bindParams = null, $bindTypes = null)
 * @method \Shadon\Mvc\Model\Query\Builder andWhere($conditions, $bindParams = null, $bindTypes = null)
 * @method \Shadon\Mvc\Model\Query\Builder orWhere($conditions, $bindParams = null, $bindTypes = null)
 * @method \Shadon\Mvc\Model\Query\Builder betweenWhere($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
 * @method \Shadon\Mvc\Model\Query\Builder notBetweenWhere($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
 * @method \Shadon\Mvc\Model\Query\Builder inWhere($expr, array $values, $operator = BuilderInterface::OPERATOR_AND)
 * @method \Shadon\Mvc\Model\Query\Builder notInWhere($expr, array $values, $operator = BuilderInterface::OPERATOR_AND)
 * @method \Shadon\Mvc\Model\Query\Builder orderBy($orderBy)
 * @method \Shadon\Mvc\Model\Query\Builder having($having)
 * @method \Shadon\Mvc\Model\Query\Builder forUpdate($forUpdate)
 * @method \Shadon\Mvc\Model\Query\Builder limit($limit, $offset = null)
 * @method \Shadon\Mvc\Model\Query\Builder groupBy($group)
 */
class Builder extends QueryBuilder
{
    public const DAY = 1;
    public const WEEK = 2;
    public const MONTH = 3;

    /**
     * 拼接多少时间前的where条件.
     *
     * @param string $field  字段名
     * @param int    $number 数字
     * @param int    $type   类型(1.天; 2.周; 3.月)
     *
     * @return self
     */
    public function timeBefore(string $field, int $number, int $type = self::DAY)
    {
        if (empty($field)) {
            return;
        }

        $where = $this->timeFormat($field, $number, $type);

        return $this->andWhere($where);
    }

    /**
     * 链式执行方法.
     *
     * @param array $bindParams
     * @param array $bindTypes
     *
     * @return mixed
     */
    public function query($bindParams = null, $bindTypes = null)
    {
        return $this->getQuery()->execute($bindParams, $bindTypes);
    }

    /**
     * 格式化时间参数条件.
     *
     * @param string $field  字段名
     * @param int    $number 数字
     * @param int    $type   类型(1.天; 2.周; 3.月)
     *
     * @return string
     */
    private function timeFormat(string $field, int $number, int $type = self::DAY)
    {
        $number = !empty($number) ? $number : 1;
        //获取当天0点的时间戳
        $endTime = strtotime(date('Y-m-d', time()));
        $startTime = '';
        switch ($type) {
            case 1:
                $startTime = $endTime - $number * 86400;
                break;
            case 2:
                $startTime = strtotime(date('Y-m-d', strtotime(" - $number week")));
                break;
            case 3:
                $startTime = strtotime(date('Y-m-d', strtotime(" - $number month")));
                break;
            default:
                $startTime = $endTime - $number * 86400;
        }

        return $field.' >= '.$startTime.' AND '.$field.' <= '.$endTime;
    }
}
