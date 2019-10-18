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

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Unserializable;
use MongoDB\Driver\WriteConcern;
use Phalcon\Db\Adapter\MongoDB\Collection as AdapterCollection;
use Phalcon\Db\Adapter\MongoDB\InsertOneResult;
use Phalcon\Di;
use Phalcon\Mvc\Collection as PhalconCollection;
use Phalcon\Mvc\Collection\Document;
use Phalcon\Mvc\Collection\Exception;
use Phalcon\Mvc\Collection\ManagerInterface;
use Phalcon\Mvc\CollectionInterface;

/**
 * Class MongoCollection.
 *
 * @property ManagerInterface _modelsManager
 */
abstract class MongoCollection extends PhalconCollection implements Unserializable
{
    // @codingStandardsIgnoreStart
    protected static $_disableEvents;
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     */
    public function setId($id): void
    {
        if (\is_object($id)) {
            $this->_id = $id;

            return;
        }

        if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
            $this->_id = new ObjectID($id);

            return;
        }

        $this->_id = $id;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Mvc\Collection::getReservedAttributes()
     */
    public function getReservedAttributes()
    {
        $reserved = [
                '_connection'         => true,
                '_dependencyInjector' => true,
                '_source'             => true,
                '_operationMade'      => true,
                '_errorMessages'      => true,
                '_dirtyState'         => true,
                '_modelsManager'      => true,
                '_skipped'            => true,
            ];

        return $reserved;
    }

    /**
     * 数组转对象.
     *
     * @param array $data
     *
     * @return \Shadon\Mvc\MongoCollection
     */
    public static function hydrator(array $data = [])
    {
        $object = new static();
        $reflect = new \ReflectionObject($object);
        foreach ($data as $name => $value) {
            if ($reflect->hasProperty($name)) {
                $prop = $reflect->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     *
     * @return bool
     */
    public function save()
    {
        $collection = $this->prepareCU();
        $exists = $this->_exists($collection);

        if (false === $exists) {
            $this->_operationMade = self::OP_CREATE;
        } else {
            $this->_operationMade = self::OP_UPDATE;
        }

        /*
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];

        $disableEvents = self::$_disableEvents;

        /*
         * Execute the preSave hook
         */
        if (false === $this->_preSave($this->_dependencyInjector, $disableEvents, $exists)) {
            return false;
        }
        $data = $this->toArray();

        /*
         * We always use safe stores to get the success state
         * Save the document
         */
        switch ($this->_operationMade) {
            case self::OP_CREATE:
                $status = $collection->insertOne($data);
                break;

            case self::OP_UPDATE:
                unset($data['_id']);
                $status = $collection->updateOne(['_id' => $this->_id], ['$set' => $data]);
                break;

            default:
                throw new Exception('Invalid operation requested for '.__METHOD__);
        }

        $success = false;

        if ($status->isAcknowledged()) {
            $success = true;

            if (false === $exists) {
                $this->_id = $status->getInsertedId();
                $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
            }
        }

        /*
         * Call the postSave hooks
         */
        return $this->_postSave($disableEvents, $success, $exists);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     *
     * @return array
     */
    public static function findById($id)
    {
        if (!\is_object($id)) {
            $classname = \get_called_class();
            $collection = new $classname();

            /** @var MongoCollection $collection */
            if ($collection->getCollectionManager()->isUsingImplicitObjectIds($collection)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        } else {
            $mongoId = $id;
        }

        return static::findFirst([['_id' => $mongoId]]);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|null $parameters
     *
     * @return array
     */
    public static function findFirst(array $parameters = null)
    {
        $className = \get_called_class();

        /** @var MongoCollection $collection */
        $collection = new $className();

        $connection = $collection->getConnection();

        return static::_getResultset($parameters, $collection, $connection, true);
    }

    /**
     * insert many data.
     *
     * ```
     * $data = [
     *     ['username' => 'admin','email' => 'admin@example.com','name' => 'Admin User'],
     *     ['username' => 'test','email' => 'test@example.com','name' => 'Test User']
     * ];
     * $robot = new Robots();
     * $robot->insertMany($data);
     * ```
     *
     * @throws Exception
     *
     * @return bool
     */
    public function insertMany(array $data)
    {
        $collection = $this->prepareCU();
        $exists = $this->_exists($collection);
        if (!$data) {
            throw new Exception("The document cannot be insertData because it doesn't exist");
        }
        $disableEvents = self::$_disableEvents;

        if (!$disableEvents) {
            if (false === $this->fireEventCancel('beforeInsertMany')) {
                return false;
            }
        }
        $connection = $this->getConnection();

        $source = $this->getSource();
        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }
        $inserData = $this->toArray();

        $tempData = $inserData;
        if (isset($tempData['created'])) {
            unset($tempData['created']);
        }
        $inserKey = array_keys($tempData);
        //过滤存在的字段，并且添加时间函数
        $data = collect($data)->flatMap(function ($item) use ($inserKey,$inserData) {
            return [collect($item)->only($inserKey)->put('created', $inserData['created'] ?: '')->toArray()];
        })->toArray();

        /**
         * Get the Collection.
         *
         * @var AdapterCollection
         */
        $collection = $connection->selectCollection($source);

        $success = false;
        /**
         * insertMany the instance.
         */
        $status = $collection->insertMany($data);

        if ($status->isAcknowledged()) {
            $success = true;

            if (false === $exists) {
                $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
            }
        }
        /*
         * Call the postSave hooks
         */
        return $this->_postSave($disableEvents, $success, $exists);
    }

    /**
     * {@inheritdoc}
     *
     * ```
     * $robot = Robots::findFirst();
     * $robot->delete();
     *
     * foreach (Robots::find() as $robot) {
     *     $robot->delete();
     * }
     * ```
     */
    public function delete()
    {
        if (!$id = $this->_id) {
            throw new Exception("The document cannot be deleted because it doesn't exist");
        }

        $disableEvents = self::$_disableEvents;

        if (!$disableEvents) {
            if (false === $this->fireEventCancel('beforeDelete')) {
                return false;
            }
        }

        if (true === $this->_skipped) {
            return true;
        }

        $connection = $this->getConnection();

        $source = $this->getSource();
        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }

        /**
         * Get the Collection.
         *
         * @var AdapterCollection
         */
        $collection = $connection->selectCollection($source);

        if (\is_object($id)) {
            $mongoId = $id;
        } else {
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        }

        $success = false;

        /**
         * Remove the instance.
         */
        $status = $collection->deleteOne(['_id' => $mongoId], ['w' => true]);

        if ($status->isAcknowledged()) {
            $success = true;

            $this->fireEvent('afterDelete');
            $this->_dirtyState = self::DIRTY_STATE_DETACHED;
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function fireEventCancel($eventName)
    {
        /*
         * Check if there is a method with the same name of the event
         */
        if (method_exists($this, $eventName)) {
            if (false === $this->{$eventName}()) {
                return false;
            }
        }

        /*
         * Send a notification to the events manager
         */
        if (false === $this->_modelsManager->notifyEvent($eventName, $this)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @todo
     *
     * @param string $field
     * @param null   $conditions
     * @param null   $finalize
     *
     * @throws Exception
     */
    public static function summatory($field, $conditions = null, $finalize = null): void
    {
        throw new Exception('The summatory() method is not implemented in the new Mvc MongoCollection');
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function create()
    {
        /** @var \Phalcon\Db\Adapter\MongoDB\Collection $collection */
        $collection = $this->prepareCU();

        /*
         * Check the dirty state of the current operation to update the current operation
         */
        $this->_operationMade = self::OP_CREATE;

        /*
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];

        /*
         * Execute the preSave hook
         */
        if (false === $this->_preSave($this->_dependencyInjector, self::$_disableEvents, false)) {
            return false;
        }

        $data = $this->toArray();
        $success = false;

        /**
         * We always use safe stores to get the success state
         * Save the document.
         */
        $result = $collection->insert($data, ['writeConcern' => new WriteConcern(1)]);
        if ($result instanceof InsertOneResult && $result->getInsertedId()) {
            $success = true;
            $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
            $this->_id = $result->getInsertedId();
        }

        /*
         * Call the postSave hooks
         */
        return $this->_postSave(self::$_disableEvents, $success, false);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     */
    public function bsonUnserialize(array $data): void
    {
        $this->setDI(Di::getDefault());
        $this->_modelsManager = Di::getDefault()->getShared('collectionManager');

        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }

        if (method_exists($this, 'afterFetch')) {
            $this->afterFetch();
        }
    }

    /**
     * select mongodb database.
     *
     * @param string $db
     *
     * @return \Phalcon\Mvc\Collection
     */
    protected function selectDb($db)
    {
        $di = $this->getDI();
        if (!$di->has('mongo_db_'.$db)) {
            $di->set('mongo_db_'.$db, function () use ($di, $db) {
                $mongoClient = $di->has('mongo_'.$db) ? $di->get('mongo_'.$db) : $di->get('mongo_default');
                /* @var \MongoDB\Client $mongoClient */
                $database = $mongoClient->selectDatabase($db);

                return $database;
            }, true);
        }

        return $this->setConnectionService('mongo_db_'.$db);
    }

    /**
     * {@inheritdoc}
     *
     * @param array               $params
     * @param CollectionInterface $collection
     * @param \MongoDb            $connection
     * @param bool                $unique
     *
     * @throws Exception
     *
     * @return array
     * @codingStandardsIgnoreStart
     */
    protected static function _getResultset($params, CollectionInterface $collection, $connection, $unique)
    {
        /*
         * @codingStandardsIgnoreEnd
         * Check if "class" clause was defined
         */
        if (isset($params['class'])) {
            $classname = $params['class'];
            $base = new $classname();

            if (!$base instanceof CollectionInterface || $base instanceof Document) {
                throw new Exception(
                    sprintf(
                        'Object of class "%s" must be an implementation of %s or an instance of %s',
                        \get_class($base),
                        CollectionInterface::class,
                        Document::class
                    )
                );
            }
        } else {
            $base = $collection;
        }

        if ($base instanceof PhalconCollection) {
            $base->setDirtyState(PhalconCollection::DIRTY_STATE_PERSISTENT);
        }

        $source = $collection->getSource();

        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }

        /**
         * @var \Phalcon\Db\Adapter\MongoDB\Collection
         */
        $mongoCollection = $connection->selectCollection($source);

        if (!\is_object($mongoCollection)) {
            throw new Exception("Couldn't select mongo collection");
        }

        $conditions = [];

        if (isset($params[0]) || isset($params['conditions'])) {
            $conditions = (isset($params[0])) ? $params[0] : $params['conditions'];
        }

        /*
         * Convert the string to an array
         */
        if (!\is_array($conditions)) {
            throw new Exception('Find parameters must be an array');
        }

        $options = [];

        /*
         * Check if a "limit" clause was defined
         */
        if (isset($params['limit'])) {
            $limit = $params['limit'];

            $options['limit'] = (int) $limit;

            if ($unique) {
                $options['limit'] = 1;
            }
        }

        /*
         * Check if a "sort" clause was defined
         */
        if (isset($params['sort'])) {
            $sort = $params['sort'];

            $options['sort'] = $sort;
        }

        /*
         * Check if a "skip" clause was defined
         */
        if (isset($params['skip'])) {
            $skip = $params['skip'];

            $options['skip'] = (int) $skip;
        }

        if (isset($params['fields']) && \is_array($params['fields']) && !empty($params['fields'])) {
            $options['projection'] = [];

            foreach ($params['fields'] as $key => $show) {
                $options['projection'][$key] = $show;
            }
        }

        /**
         * Perform the find.
         */
        $cursor = $mongoCollection->find($conditions, $options);

        $cursor->setTypeMap(['root' => \get_class($base), 'document' => 'array']);

        if (true === $unique) {
            /*
             * Looking for only the first result.
             */
            return current($cursor->toArray());
        }

        /**
         * Requesting a complete resultset.
         */
        $collections = [];
        foreach ($cursor as $document) {
            /*
             * Assign the values to the base object
             */
            $collections[] = $document;
        }

        return $collections;
    }

    /**
     * {@inheritdoc}
     *
     * @param \MongoCollection $collection
     *
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function _exists($collection)
    {
        // @codingStandardsIgnoreStart
        if (!$id = $this->_id) {
            return false;
        }

        if (!$this->_dirtyState) {
            return true;
        }

        if (\is_object($id)) {
            $mongoId = $id;
        } else {
            /*
             * Check if the model use implicit ids
             */
            if ($this->_modelsManager->isUsingImplicitObjectIds($this)) {
                $mongoId = new ObjectID($id);
            } else {
                $mongoId = $id;
            }
        }

        /**
         * Perform the count using the function provided by the driver.
         */
        $exists = $collection->count(['_id' => $mongoId]) > 0;

        if ($exists) {
            $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
        } else {
            $this->_dirtyState = self::DIRTY_STATE_TRANSIENT;
        }

        return $exists;
    }
}
