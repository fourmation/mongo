<?php
namespace Mongo\Mapper;

use MongoClient;
use MongoId;
use MongoCursor;
use MongoCollection;
use Exception;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\HydratingResultSet;

use Zend\Stdlib\Hydrator\ClassMethods;

use Zend\Di\ServiceLocator;

use Zend\Stdlib\Hydrator\Filter\MethodMatchFilter;
use Zend\Stdlib\Hydrator\Filter\FilterComposite;

use Mongo\Hydrator\Strategy\MongoIdStrategy;

use ZfcBase\EventManager\EventProvider;

/**
 * Class DbAbstract
 *
 * Entities can use _id as the unique Identifier
 *
 * @package Zf2Mongo\Mapper
 */
abstract class DbAbstract extends EventProvider
{
    /**
     * @var MongoClient
     */
    protected $dbAdapter;

    /**
     * @var string
     */
    protected $database;

    /**
     * @var string
     */
    protected $collection;

    /**
     * @var MongoCollection
     */
    protected $collectionPrototype = null;

    /**
     * @var ClassMethods
     */
    protected $hydrator;

    /**
     * @var MongoCursor
     */
    protected $lastCursor;

    /**
     * @var object
     */
    protected $entityPrototype;

    /**`
     * @var bool
     */
    protected $isInitialised = false;

    /**
     * Initialise Class
     * @return bool
     * @throws \Exception
     */
    public function initialise() {
        if ($this->isInitialised) {
            return true;
        }

        if (! $this->hydrator instanceof ClassMethods) {
            $this->getHydrator();
        }
        
        if (!is_object($this->entityPrototype)) {
            throw new \Exception('No entity prototype set');
        }

        $this->isInitialised = true;
    }

    /**
     * Return a Hydrating Resultset object
     * @see http://www.php.net/manual/en/class.mongocollection.php
     *
     * @param array $query
     * @param array $fields
     * @param null $entityPrototype
     * @param null $hydrator
     * @param bool $findAll
     * @return bool|HydratingResultSet
     */
    public function find($query = array(), $fields = array(), $entityPrototype = null, $hydrator = null, $findAll = true,
         $order = array())
    {
        $this->initialise();

        $collection = $this->getCollectionPrototype();

        if ($findAll) {
            $cursor = $collection->find($query, $fields);

            if (is_null($cursor)) return false;
            $resultSet = new HydratingResultSet($hydrator ?: $this->getHydrator(),
                $entityPrototype ?: clone $this->getEntityPrototype());

            $resultSet->initialize($cursor);
        } else {
            $cursor = $collection->findOne($query, $fields);

            if (is_null($cursor)) return false;
            $resultSet = $this->getHydrator()->hydrate($cursor,
                $entityPrototype ?: clone $this->getEntityPrototype());

        }

        if ( ! empty($order)) {
            $cursor->sort($order);
        }

        // Save the cursor to the object for raw output
        $this->setLastCursor($cursor);

        return $resultSet;
    }

    /**
     * Return a Hydrating Resultset object Alias of find
     * @see http://www.php.net/manual/en/class.mongocollection.php
     *
     * @param array $query
     * @param array $fields
     * @param null $entityPrototype
     * @param null $hydrator
     * @param bool $findAll
     * @return bool|HydratingResultSet
     */
    public function select($query = array(), $fields = array(), $entityPrototype = null, $hydrator = null, $findAll = true)
    {
        return $this->find($query, $fields, $entityPrototype, $hydrator, $findAll);
    }

    /**
     * Return a single document based on standard MongoCollection format
     * @see http://www.php.net/manual/en/class.mongocollection.php
     *
     * @param array $query
     * @param array $fields
     * @param null $entityPrototype
     * @param HydratorInterface $hydrator
     * @return bool|HydratingResultSet
     */
    public function findOne($query = array(), $fields = array(), $entityPrototype = null, HydratorInterface $hydrator = null)
    {
        return $this->find($query, $fields, $entityPrototype, $hydrator, false);
    }

    /**
     * Return a single document based on standard MongoCollection format
     * Alias for FindOne
     *
     * @see http://www.php.net/manual/en/class.mongocollection.php
     *
     * @param array $query
     * @param array $fields
     * @param null $entityPrototype
     * @param HydratorInterface $hydrator
     * @return bool|HydratingResultSet
     */
    public function selectOne($query = array(), $fields = array(), $entityPrototype = null, HydratorInterface $hydrator = null)
    {
        return $this->find($query, $fields, $entityPrototype, $hydrator, false);
    }

    /**
     * Insert Document
     * @param $entity
     * @param array $options
     * @return mixed
     */
    public function insert($entity, $options = array())
    {
        $this->initialise();
        $collection = $this->getCollectionPrototype();

        $rowData = $this->getHydrator()->extract($entity);
        // use _id as the id in entities where you want the mongo id to be the id
        if (is_null($rowData['id'])) unset($rowData['id']);

        $collection->insert($rowData, $options);

        return $rowData;
    }

    /**
     * Update Document
     * @see http://www.php.net/manual/en/mongocollection.update.php
     *
     * @param $entity
     * @param array|null $where
     * @param array $options
     * @param null $collectionName
     * @param HydratorInterface $hydrator
     * @return bool
     */
    protected function update($entity, array $where = null, array $options = array(), $collectionName = null, HydratorInterface $hydrator = null)
    {
        $this->initialise();
        $collectionName = $collectionName ?: $this->collection;
        $hydrator = (!$hydrator)?$this->getHydrator():$hydrator;

        $this->getDbAdapter()->selectCollection($this->database, $collectionName);
        $collection = $this->getCollectionPrototype();

        if (! $where) {
            $id = $entity->get_id();
            if ($id instanceof MongoId) {
                $id = $this->mongoId($id);
            }
            $where = array('_id' => $id);
        }

        $rowData = $hydrator->extract($entity);

        // use _id as the id in entities where you want the mongo id to be the id
        if (is_null($rowData['id'])) unset($rowData['id']);

        return $collection->update($where, $rowData, $options);
    }

    /**
     * Delete Document
     * @param $entity
     * @param array $where
     * @param array $options
     *
     * @return mixed
     */
    public function remove($entity, array $where = null, array $options = array())
    {
        $this->initialise();

        if (! $where) {
            $id = $entity->get_id();
            if ($id instanceof MongoId) {
                $id = $this->mongoId($id);
            }
            $where = array('_id' => $id);
        }

        $collection = $this->getCollectionPrototype();
        $result = $collection->remove($where, $options);

        return $result;
    }

    /**
     * Remove Document (Alias of Delete)
     * @param $entity
     * @param array $where
     * @param array $options
     * @return mixed
     */
    public function delete($entity, array $where = null, array $options = array())
    {
        return $this->remove($entity, $where, $options);
    }

    /**
     * Generate a MongoId Object
     * @param $id
     * @return bool|MongoId
     */
    public function mongoId($id)
    {
        if (empty($id)) return false;
        return new MongoId($id);
    }

    /**
     * Get the Mongo Collection Prototype
     * @var collectionPrototype MongoDb
     * @return MongoCollection
     */
    public function getCollectionPrototype()
    {
        /** @var \Mongo\Db\Adapter\Adapter $adapter */
        $adapter = $this->getDbAdapter();
        $collection = $adapter->getCollection($this->database, $this->collection);

        // Fetch the collection to this object
        if (is_null($this->collectionPrototype) && !is_null($collection)) {
            $this->collectionPrototype = $collection;
        }

        // Return the collection
        return $this->collectionPrototype;
    }

    /**
     * Set the DB Adapter
     * @param array $config
     *
     * @return $this
     * @throws
     */
    public function setDbAdapter($adapter)
    {
        $this->dbAdapter = $adapter;
        return $this;
    }

    /**
     * Get MongoClient Instance
     * @return MongoClient
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * Set Entity Prototype
     * @param mixed $entityPrototype
     * @return $this
     */
    public function setEntityPrototype($entityPrototype)
    {
        $this->entityPrototype = $entityPrototype;

        return $this;
    }

    /**
     * Get Entity Prototype
     * @return mixed
     */
    public function getEntityPrototype()
    {
        return $this->entityPrototype;
    }

    /**
     * Set Hydrator
     * @param $hydrator ClassMethods
     *
     * @return $this
     */
    public function setHydrator(ClassMethods $hydrator)
    {
        $this->hydrator = $hydrator;
        $this->hydrator->addStrategy('_id', new MongoIdStrategy());
        return $this;
    }

    /**
     * Get Hydrator
     * @return ClassMethods
     */
    public function getHydrator()
    {
        if (!$this->hydrator) {
            // use underscore separated keys by default
            $this->hydrator = new ClassMethods();
            $this->hydrator->addStrategy('_id', new MongoIdStrategy());
        }
        return $this->hydrator;
    }

    /**
     * Set last cursor object
     * @param \MongoCursor $lastCursor
     * @return $this
     */
    public function setLastCursor($lastCursor)
    {
        $this->lastCursor = $lastCursor;

        return $this;
    }

    /**
     * Get last cursor object
     * @return \MongoCursor
     */
    public function getLastCursor()
    {
        return $this->lastCursor;
    }

}
