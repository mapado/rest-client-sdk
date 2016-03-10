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
    private $elements;
    private $count = 0;
    private $totalItems = 0;

    public function __construct($response)
    {
        $this->elements = $response['hydra:member'];
        $this->count = count($this->elements);

        if (!empty($response['hydra:totalItems'])) {
            $this->totalItems = $response['hydra:totalItems'];
        }
    }

    /**
     *  return array
     */
    public function toArray()
    {
        return $this->elements;
    }

    public function serialize()
    {
        return serialize($this->elements);
    }

    public function unserialize($values)
    {
        $this->elements = unserialize($values);
    }

    public function count()
    {
        return $this->count;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    /**
     *  return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
