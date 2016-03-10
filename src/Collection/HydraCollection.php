<?php

namespace Mapado\RestClientSdk\Collection;

use \ArrayIterator;

/**
 * Class HydraCollection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraCollection implements \IteratorAggregate, \Serializable, \Countable, \ArrayAccess
{
    /**
     * @var array the elements of the collection
     */
    private $elements;

    /**
     * @var integer the number of elements
     */
    private $count = 0;

    /**
     * @param array response - The Hydra data as an array
     */
    public function __construct($response)
    {
        $this->elements = $response['hydra:member'];
        $this->count = count($this->elements);
    }

    /**
     *  toArray
     *
     *  @return array
     */
    public function toArray()
    {
        return $this->elements;
    }

    /**
     *  serialize
     *
     *  @return string
     */
    public function serialize()
    {
        return serialize($this->elements);
    }

    /**
     *  unserialize
     */
    public function unserialize($values)
    {
        $this->elements = unserialize($values);
    }

    /**
     *  count
     *
     *  @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     *  ArrayAccess implementation of offsetSet()
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
     *  ArrayAccess implementation of offsetExists()
     *
     *  @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     *  ArrayAccess implementation of offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    /**
     *  ArrayAccess implementation of offsetGet()
     *
     *  @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    /**
     * getIterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     *  getTotalItems
     *
     *  @return integer
     */
    public function getTotalItems()
    {
        return $this->count();
    }
}
