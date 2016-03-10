<?php

namespace Mapado\RestClientSdk\Collection;

/**
 * Class HydraCollection
 *
 * @author Florent Clerc <florent.clerc@mapado.com>
 */
class HydraCollection implements \Iterator, \Serializable, \Countable, \ArrayAccess
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

    public function current()
    {
        return current($this->elements);
    }

    public function key()
    {
        return key($this->elements);
    }

    public function next()
    {
        return next($this->elements);
    }

    public function rewind()
    {
        reset($this->elements);
    }

    public function valid()
    {
        $key = key($this->elements);
        return ($key !== null && $key !== false);
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

    /**
     *  return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
