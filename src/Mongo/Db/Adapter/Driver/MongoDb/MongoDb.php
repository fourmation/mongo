<?php
namespace Mongo\Db\Adapter\Driver\MongoDb;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\Profiler;
use Mongo\Db\Adapter\Driver\MongoDb\Connection;

class MongoDb implements DriverInterface, Profiler\ProfilerAwareInterface
{

    protected $profiler;

    protected $connection;

    /**
     * Unused!
     */
    public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE) {}
    public function createStatement($sqlOrResource = null) {}
    public function createResult($resource) {}
    public function getPrepareType() {}
    public function formatParameterName($name, $type = null) {}
    public function getLastGeneratedValue() {}


    /**
     * Create this driver instance
     * @param $connection
     * @throws \Exception
     */
    public function __construct( $connection )
    {
        if (!$connection instanceof Connection) {
            $connection = new Connection($connection);
        }

        $this->registerConnection($connection);
    }


    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    public function checkEnvironment()
    {
        if (!extension_loaded('mongo')) {
            throw new Exception\RuntimeException('The MongoDb extension is required for this adapter but the extension is not loaded');
        }
    }


    /**
     * @param Profiler\ProfilerInterface $profiler
     * @return MongoDB
     */
    public function setProfiler(Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }


    /**
     * @return null|Profiler\ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function registerConnection( $connection )
    {
        $this->connection = $connection;
    }


}