<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Mongo\Db\Adapter;

use Zend\Db\ResultSet;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Profiler\ProfilerAwareInterface;
use Zend\Code\Exception;
use MongoCollection;

class Adapter implements AdapterInterface, ProfilerAwareInterface
{
    /**
     * @var \Zend\Db\Adapter\Profiler\ProfilerInterface
     */
    protected $profiler = null;


    /**
     * @var ResultSet\ResultSetInterface
     */
    protected $queryResultSetPrototype = null;


    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($driver, $profiler = null)
    {
        if (is_array($driver)) {
            $parameters = $driver;
            $driver = $this->createDriver($parameters);
        } elseif (!$driver instanceof \Zend\Db\Adapter\Driver\DriverInterface) {
            throw new Exception\InvalidArgumentException(
                'The supplied or instantiated driver object does not implement Zend\Db\Adapter\Driver\DriverInterface'
            );
        }
        $driver->checkEnvironment();

        $this->driver = $driver;
        $this->queryResultSetPrototype = new ResultSet\ResultSet();

        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws Exception\InvalidArgumentException
     */
    protected function createDriver($parameters)
    {
        if (!isset($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects a "driver" key to be present inside the parameters');
        }

        if ($parameters['driver'] instanceof \Zend\Db\Adapter\Driver\DriverInterface) {
            return $parameters['driver'];
        }

        if (!is_string($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects a "driver" to be a string or instance of DriverInterface');
        }

        $options = array();
        if (isset($parameters['options'])) {
            $options = (array) $parameters['options'];
            unset($parameters['options']);
        }

        $driverName = strtolower($parameters['driver']);
        switch ($driverName) {
            case 'mongodb':
                $driver = new Driver\MongoDb\MongoDb($parameters);
                break;
        }

        if (!isset($driver) || !$driver instanceof \Zend\Db\Adapter\Driver\DriverInterface) {
            throw new Exception\InvalidArgumentException('DriverInterface expected', null, null);
        }

        return $driver;
    }

    /**
     * Fetch the requested collection
     * @param   string  $database
     * @param   string  $collection
     * @return  MongoCollection
     */
    public function getCollection($database, $collection)
    {
        return $this->getDriver()->getConnection()->getResource()->{$database}->{$collection};
    }

    /**
     * @param \Zend\Db\Adapter\Profiler\ProfilerInterface $profiler
     * @return Adapter
     */
    public function setProfiler( \Zend\Db\Adapter\Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        if ($this->driver instanceof \Zend\Db\Adapter\Profiler\ProfilerAwareInterface) {
            $this->driver->setProfiler($profiler);
        }

        return $this;
    }

    /**
     * @return null|\Zend\Db\Adapter\Profiler\ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * getDriver()
     *
     * @throws \Zend\Db\Adapter\Exception\RuntimeException
     * @return Driver\MongoDb\MongoDb
     */
    public function getDriver()
    {
        if ($this->driver == null) {
            throw new Exception\RuntimeException('Driver has not been set or configured for this adapter.');
        }
        return $this->driver;
    }

    /**
     * @return \Zend\Db\Adapter\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        return $this->platform;
    }

}