<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Collection;

/**
 * @template E
 * @template ExtraProperty
 * 
 * @implements \IteratorAggregate<E>
 * @implements \ArrayAccess<int, E>
 */
class Collection implements
    \IteratorAggregate,
    \Serializable,
    \Countable,
    \ArrayAccess
{
    /**
     * The elements of the collection.
     *
     * @var array<E>
     */
    private $elements;

    /**
     * extra properties that are not the main list but linked data
     * It can be "_embed" or "_links" for HAL response
     * or "hydra:totalItems" for JSON-LD
     * or anything you want to really ("foo" is OK for exemple)
     *
     * @var array<string, ExtraProperty>
     */
    private $extraProperties;

    /**
     * @param array<E> $elements the data elements as an array
     * @param array<string, ExtraProperty> $extraProperties the extra properties
     */
    public function __construct(
        array $elements = [],
        array $extraProperties = []
    ) {
        $this->elements = $elements;
        $this->extraProperties = $extraProperties;
    }

    /**
     *  @return array<E>
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
        $unserializedValues = unserialize($values);

        if (is_array($unserializedValues)) {
            $this->elements = $unserializedValues;
        }
    }

    /**
     * Returns inner elements collection.
     * 
     * @return array<E>
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
     * @phpstan-param E $value
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
     * @phpstan-return E|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->elements[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     * 
     * @return \ArrayIterator<int, E>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->elements);
    }

    /**
     * @return array<ExtraProperty>
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function getStringExtraProperty(string $key): ?string
    {
        $value = $this->getExtraProperty($key);

        return is_string($value) ? $value : null;
    }

    public function getIntExtraProperty(string $key): ?int
    {
        $value = $this->getExtraProperty($key);

        return is_int($value) ? $value : null;
    }

    /**
     * return the value of an extra property
     *
     * @phpstan-return ?ExtraProperty
     */
    public function getExtraProperty(string $key)
    {
        if (isset($this->extraProperties[$key])) {
            return $this->extraProperties[$key];
        }

        return null;
    }
}
