<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Collection;

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
    public function __construct(
        array $elements = [],
        array $extraProperties = []
    ) {
        $this->elements = $elements;
        $this->extraProperties = $extraProperties;
    }

    /**
     *  @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $values
     */
    public function __unserialize($values): void
    {
        $this->elements = unserialize($values);
    }

    /**
     * Returns inner elements collection.
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated `serialize` method is deprecated, `__serialize` is used instead. See https://php.watch/versions/8.1/serializable-deprecated
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @deprecated `unserialize` method is deprecated, `__unserialize` is used instead. See https://php.watch/versions/8.1/serializable-deprecated
     */
    public function unserialize($data): void
    {
        $this->__unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * Returns element count in collection.
     */
    public function getTotalItems(): int
    {
        return $this->count();
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|null $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|null $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|null $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|null $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->elements[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->elements);
    }

    /**
     * getExtraProperties
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    /**
     * return the value of an extra property
     *
     * @return mixed
     */
    public function getExtraProperty(string $key)
    {
        if (isset($this->extraProperties[$key])) {
            return $this->extraProperties[$key];
        }
    }
}
