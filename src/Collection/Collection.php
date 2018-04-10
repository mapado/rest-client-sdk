<?php

namespace Mapado\RestClientSdk\Collection;

use ArrayIterator;

/**
 * Class Collection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class Collection implements \IteratorAggregate, \Serializable, \Countable, \ArrayAccess
{
    /**
     * The elements of the collection.
     *
     * @var array
     */
    private $elements;

    /**
     * extra properties that are not the main list but linked data
     * It can be "_embed" or "_links" for HAL response
     * or "hydra:totalItems" for JSON-LD
     * or anything you want to really ("foo" is OK for exemple)
     *
     * @var array
     */
    private $extraProperties;

    /**
     * @param array $elements the data elements as an array
     * @param array $extraProperties the extra properties
     */
    public function __construct(array $elements = [], array $extraProperties = [])
    {
        $this->elements = $elements;
        $this->extraProperties = $extraProperties;
    }

    /**
     * Returns inner elements collection.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($values)
    {
        $this->elements = unserialize($values);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->elements);
    }

    /**
     * Returns element count in collection.
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->count();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * getExtraProperties
     *
     * @return array
     */
    public function getExtraProperties()
    {
        return $this->extraProperties;
    }

    /**
     * return the value of an extra property
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getExtraProperty($key)
    {
        if (isset($this->extraProperties[$key])) {
            return $this->extraProperties[$key];
        }
    }
}
