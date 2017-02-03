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
     * The elements of the collection.
     *
     * @var array
     */
    private $elements;

    /**
     * @param array $response The Hydra data as an array.
     */
    public function __construct($elements = [])
    {
        $this->elements = $elements;
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
}
