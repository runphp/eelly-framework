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

namespace Shadon\Mvc;

use Phalcon\Di;
use Phalcon\Mvc\Model as MvcModel;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Factory;

/**
 * Class Model.
 */
abstract class Model extends MvcModel
{
    public function initialize(): void
    {
        $this->setWriteConnectionService('dbMaster');
        $this->setReadConnectionService('dbSlave');
        $this->skipAttributes([
            'update_time',
        ]);
    }

    /**
     * @param null $parameters
     *
     * @return ResultsetInterface
     */
    public static function find($parameters = null): ResultsetInterface
    {
        try {
            return parent::find($parameters);
        } catch (\PDOException $e) {
            if ('42S22' == $e->getCode()) {
                $di = Di::getDefault();
                $modelsMetadata = $di->getShared('modelsMetadata');
                $modelsMetadata->reset();

                return parent::find($parameters);
            } else {
                throw $e;
            }
        }
    }

    /**
     * save with transaction.
     *
     * @param null $data
     * @param null $whiteList
     */
    public function saveWithTransaction(\Phalcon\Mvc\Model\TransactionInterface $transaction, $data = null, $whiteList = null)
    {
        $this->setTransaction($transaction);
        $success = $this->save($data, $whiteList);
        if (false == $success) {
            foreach ($this->getMessages() as $message) {
                $transaction->rollback($message->getMessage());
            }
        }

        return $success;
    }

    /**
     * create builder.
     *
     * @param mixed  $models
     * @param string $alias  设置别名，用于连表别名
     *
     * @return \Shadon\Mvc\Model\Query\Builder
     */
    public static function createBuilder($models = null, $alias = null)
    {
        if (null === $models) {
            $models = static::class;
        }
        if ($alias) {
            return Di::getDefault()->getShared('modelsManager')->createBuilder()->addFrom($models, $alias);
        }

        return Di::getDefault()->getShared('modelsManager')->createBuilder()->from($models);
    }

    /**
     * 返回分页数组.
     *
     * @param $data Model查找结果集|如 $data = OauthModuleService::find();
     * @param int $page  当前页数
     * @param int $limit 分页页数
     *
     * @return array
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     */
    public function pagination($data, int $page = 1, int $limit = 10): array
    {
        if (empty($data)) {
            return [];
        }
        $paginator = new PaginatorModel(
            [
                'data'  => $data,
                'limit' => $limit,
                'page'  => $page,
            ]
        );
        $page = $paginator->getPaginate();
        foreach ($page->items as $key => $item) {
            $return['items'][$key] = $item->toArray();
        }
        if (empty($return['items'])) {
            return [];
        }
        $return['page'] = [
            'total_pages' => $page->total_pages,
            'total_items' => $page->total_items,
            'limit'       => $page->limit,
        ];

        return self::arrayToHump($return);
    }

    /**
     * 原生sql语句返回分页数组.
     *
     * @param string $sql   sql语句
     * @param int    $page  当前页数
     * @param int    $limit 分页页数
     *
     * @return array
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     */
    public function paginationSql(string $sql, int $page = 1, int $limit = 10): array
    {
        $start = ($page - 1) * $limit;
        $count = \count($this->getReadConnection()->fetchAll($sql));
        $sql .= " limit $start,$limit ";
        $data = $this->getReadConnection()->fetchAll($sql);
        if (empty($data)) {
            return [];
        }

        $total_pages = ceil($count / $limit);
        $return['items'] = $data;
        $return['page'] = [
            'total_pages' => ceil($count / $limit),
            'total_items' => $count,
            'limit'       => $limit,
        ];

        return self::arrayToHump($return);
    }

    /**
     * 获取字段.
     *
     * @param string $field
     * @param string $alias 设置别名，用于连表别名
     *
     * @return string
     * @requestExample(base)
     * @returnExample([role_id,role_name,default_permission,created_time,update_time])
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     *
     * @since 2017-7-27
     */
    public static function getField(string $field = 'base', string $alias = null): string
    {
        $stringField = \get_called_class()::FIELD_SCOPE[$field] ?? $field;
        if ($stringField && $alias) {
            $data = explode(',', $stringField);
            foreach ($data as $key => $val) {
                $data[$key] = "$alias.$val";
            }
            $stringField = implode(',', $data);
        }

        return $stringField;
    }

    /**
     * 数组转驼峰.
     *
     * @param array $data 等待转换的数组
     *
     * @return array 返回转驼峰之后的数组
     * @requestExample({"user_id":"1","user_name":"liangxinyi"})
     * @returnExample({"userId":"1","userName":"liangxinyi" })
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     *
     * @since 2017-8-23
     */
    public static function arrayToHump(array &$data)
    {
        if (empty($data)) {
            return [];
        }
        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                $key = preg_replace_callback('/(_)([a-z])/i', function ($matches) use (&$data,&$key) {
                    unset($data[$key]);

                    return ucfirst($matches[2]);
                }, $key);
                $temp[$key] = $value;
                if (\is_array($value)) {
                    $temp[$key] = self::arrayToHump($value);
                }
            }
        }

        return $temp;
    }

    /**
     * queryBuilder适配器，返回分页数组.
     *
     * @param mixed $builder Model查找结果集|如 $builder = Bank::createBuilder();
     * @param int   $page    当前页数
     * @param int   $limit   分页页数
     *
     * @return array
     *
     * @author zhangyingdi<zhangyingdi@eelly.net>
     */
    public function queryBuilderPagination($builder, int $page = 1, int $limit = 20): array
    {
        if (empty($builder)) {
            return [];
        }
        $options = [
            'builder' => $builder,
            'limit'   => $limit,
            'page'    => $page,
            'adapter' => 'queryBuilder',
        ];
        $paginator = Factory::load($options);
        $page = $paginator->getPaginate();

        foreach ($page->items as $key => $item) {
            $return['items'][$key] = $item->toArray();
        }
        if (empty($return['items'])) {
            return [];
        }

        $return['page'] = [
            'total_pages' => $page->total_pages,
            'total_items' => $page->total_items,
            'current'     => $page->current,
            'limit'       => $page->limit,
        ];

        return self::arrayToHump($return);
    }

    /**
     * 封装phalcon model的update函数，实现仅更新数据变更字段，而非所有字段更新.
     *
     * @param array|null $data
     * @param null       $whiteList
     *
     * @return int
     */
    public function iupdate(array $data = null, $whiteList = null)
    {
        if (\count($data) > 0) {
            //获取当前模型驿应的数据表所有字段
            $attributes = $this->getModelsMetaData()->getAttributes($this);
            //取所有字段和需要更新的数据字段的差集，并过滤
            $this->skipAttributesOnUpdate(array_diff($attributes, array_keys($data)));
        }

        return parent::update($data, $whiteList) ? $this->getWriteConnection()->affectedRows() : 0;
    }

    /**
     * 自定义封装，通过传过来的where跟data更新数据.
     *
     * @param array $where 更新条件
     * @param array $set   更新的数据
     *
     * @requestExample({"where":{"pk_id":10}, "set":{"param_name":"测试编码"}})
     *
     * @return int
     */
    public function arrayUpdate(array $where = [], array $set = [])
    {
        if (empty($where) || empty($set)) {
            return false;
        }

        $tableName = $this->getSource();
        $setSql = $whereSql = '';
        //拼接条件
        foreach ($set as $sk => $sv) {
            $setSql .= $sk.' = "'.$sv.'",';
        }
        foreach ($where as $wk => $wv) {
            $whereSql .= $wk.' = "'.$wv.'" AND ';
        }

        $setSql = rtrim($setSql, ',');
        $whereSql = rtrim($whereSql, ' AND ');
        $sql = 'UPDATE `'.$tableName.'` SET '.$setSql.' WHERE '.$whereSql;
        $this->getDI()->get('dbMaster')->execute($sql);

        return (int) $this->getWriteConnection()->affectedRows();
    }

    /*
    **
    * 批量更新，通过主键ID.
    * code
    *  $ids = [1,3,4];
    * code.
    *
    * @param array $ids 一维数组的主键ID
    *
    * @return int
    *
    * @author 肖俊明<xiaojunming@eelly.net>
    *
    * @since 2017年10月30日
    */
    public function batchUpdate(array $ids, array $set)
    {
        $ids = array_map('intval', $ids);
        if (empty($ids)) {
            return 0;
        }
        $setSql = '';
        //拼接条件
        foreach ($set as $sk => $sv) {
            $setSql .= $sk.' = "'.$sv.'",';
        }
        $idStr = implode(',', $ids);
        $tableName = $this->getSource();
        $setSql = rtrim($setSql, ',');
        $sql = 'UPDATE `'.$tableName.'` SET '.$setSql.' WHERE '.$this->pk.' IN ('.$idStr.')';
        $this->getWriteConnection()->execute($sql);

        return (int) $this->getWriteConnection()->affectedRows();
    }

    /**
     * 批量删除，通过主键ID.
     * code
     *  $ids = [1,3,4];
     * code.
     *
     * @param array $ids 一维数组的主键ID
     *
     * @return int
     *
     * @author 肖俊明<xiaojunming@eelly.net>
     *
     * @since 2017年10月30日
     */
    public function batchDelete(array $ids)
    {
        $ids = array_map('intval', $ids);
        if (empty($ids)) {
            return false;
        }
        $idStr = implode(',', $ids);
        $tableName = $this->getSource();
        $sql = 'DELETE FROM `'.$tableName.'` WHERE '.$this->pk.' IN ('.$idStr.')';
        $this->getWriteConnection()->execute($sql);

        return (int) $this->getWriteConnection()->affectedRows();
    }

    /**
     * 自定义封装，批量插入.
     *
     * @param array $data 需要插入的数据
     *
     * @requestExample({"data":[{"code":"test", "paramName":"测试编码","paramDesc":"这个编码是测试数据","status":1,"createdTime":1503560249}]})
     *
     * @return int
     */
    public function batchCreate(array $data = [])
    {
        if (empty($data)) {
            return 0;
        }

        $keys = array_keys(reset($data));
        $keys = array_map(function ($key) {
            return "`{$key}`";
        }, $keys);
        $keys = implode(',', $keys);

        $sql = 'INSERT INTO '.$this->getSource()." ({$keys}) VALUES ";
        //拼接插入的数据
        foreach ($data as $v) {
            $v = array_map(function ($value) {
                return "'{$value}'";
            }, $v);
            $values = implode(',', array_values($v));
            $sql .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');
        $this->getDI()->get('dbMaster')->execute($sql);

        return (int) $this->getWriteConnection()->affectedRows();
    }
    
    /**
     * 执行部分比较复杂的sql，平时不建议使用
     *
     * @param string $sql 原生sql
     * @return array|void
     * @author zhangyingdi<zhangyingdi@eelly.net>
     * @since 2019.03.28
     */
    public function sqlQuery(string $sql)
    {
        if (empty($sql)) {
            return;
        }

        $this->getDI()->get('dbMaster');

        return $this->getReadConnection()->fetchAll($sql);
    }
}
