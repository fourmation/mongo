<?php
namespace Mongo\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\DefaultStrategy;

class MongoIdStrategy extends DefaultStrategy
{
    /**
     * On extract convert string to Mongo Id
     *
     * @param $value
     * @return string
     */
    public function extract($value)
    {
        if (is_string($value)) {
            return new \MongoId($value);
        }

        return $value;
    }

    /**
     * On hydrate convert Mongo Id to string
     *
     * @param $value
     * @return string
     */
    public function hydrate($value)
    {
        if (is_object($value)) {
            return (string) $value;
        }

        return $value;
    }
}
