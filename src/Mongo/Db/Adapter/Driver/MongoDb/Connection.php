<?php
namespace Mongo\Db\Adapter\Driver\MongoDb;

use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\Profiler;
use MongoClient;

class Connection
{

    protected $connectionInfo = array();

    protected $resource = null;


    /**
     * Constructor
     *
     * @param array|mysqli|null $connectionInfo
     * @throws \Zend\Db\Adapter\Exception\InvalidArgumentException
     */
    public function __construct($connectionInfo = null)
    {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        } elseif ($connectionInfo instanceof \MongoClient) {
            $this->setResource($connectionInfo);
        } elseif (null !== $connectionInfo) {
            throw new Exception\InvalidArgumentException('$connection must be an array of parameters, a mysqli object or null');
        }
    }


    /**
     * @throws MongoConnectionException
     */
    public function connect()
    {

        if ($this->isConnected()) {
            return;
        }

        $options = array("connect" => TRUE);

        // Check if authentication is required
        if ($this->connectionInfo['auth']['requireAuthentication'] == true) {
            if ($this->connectionInfo['auth']['username'] == '') {
                throw \Exception("Mongo Authentication is selected, but the username is empty.");
            }

            $options['username'] = $this->connectionInfo['auth']['username'];
            $options['password'] = $this->connectionInfo['auth']['password'];
        }

        // Create the DSN
        $connectString = sprintf("mongodb://%s:%d",
            $this->connectionInfo['hostname'],
            $this->connectionInfo['port']
        );

        if ($connectString == '') return false;

        if ($connectString == '') {
            die(
                'hg'
            );
        }
        // Connect!
        $this->resource = new MongoClient($connectString, $options);
    }


    public function setConnectionParameters( $config )
    {
        $this->connectionInfo = $config;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Fetch the mongo resource
     * @return \Mongo
     */
    public function getResource()
    {
        $this->connect();
        return $this->resource;
    }


    public function isConnected()
    {
        return ($this->resource !== null ? true : false);
    }
}