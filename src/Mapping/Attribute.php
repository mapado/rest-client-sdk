<?php

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class Attribute
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Attribute
{
    /**
     * @var string
     */
    private $serializedKey;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $isIdentifier;

    /**
     * @var string
     */
    private $attributeName;

    /**
     * Constructor.
     *
     * @param string      $serializedKey
     * @param string|null $attributeName
     * @param string      $type
     * @param bool        $isIdentifier
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $serializedKey,
        $attributeName = null,
        $type = 'string',
        $isIdentifier = false
    ) {
        if (empty($serializedKey)) {
            throw new \InvalidArgumentException('attribute name must be set');
        }

        $this->serializedKey = $serializedKey;
        $this->attributeName = $attributeName ?: $this->serializedKey;
        $this->type = $type;
        $this->isIdentifier = $isIdentifier;
    }

    /**
     * Getter for serializedKey
     *
     * @return string
     */
    public function getSerializedKey()
    {
        return $this->serializedKey;
    }

    /**
     * Setter for serializedKey
     *
     * @param string $serializedKey
     *
     * @return Attribute
     */
    public function setSerializedKey($serializedKey)
    {
        $this->serializedKey = $serializedKey;

        return $this;
    }

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for type
     *
     * @param string $type
     *
     * @return Attribute
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Getter for isIdentifier
     *
     * @return bool
     */
    public function isIdentifier()
    {
        return $this->isIdentifier;
    }

    /**
     * Setter for isIdentifier
     *
     * @param bool $isIdentifier
     *
     * @return Attribute
     */
    public function setIsIdentifier($isIdentifier)
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    /**
     * Getter for attributeName
     *
     * @return string
     */
    public function getAttributeName()
    {
        return $this->attributeName;
    }

    /**
     * Setter for attributeName
     *
     * @param string $attributeName
     *
     * @return Attribute
     */
    public function setAttributeName($attributeName)
    {
        $this->attributeName = $attributeName;

        return $this;
    }
}
