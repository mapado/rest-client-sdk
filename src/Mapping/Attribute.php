<?php

declare(strict_types=1);

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
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $serializedKey,
        string $attributeName = null,
        string $type = null,
        bool $isIdentifier = false,
    ) {
        if (empty($serializedKey)) {
            throw new \InvalidArgumentException('attribute name must be set');
        }

        $this->serializedKey = $serializedKey;
        $this->attributeName = $attributeName ?? $this->serializedKey;
        $this->type = $type ?? 'string';
        $this->isIdentifier = $isIdentifier;
    }

    public function getSerializedKey(): string
    {
        return $this->serializedKey;
    }

    public function setSerializedKey(string $serializedKey): self
    {
        $this->serializedKey = $serializedKey;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isIdentifier(): bool
    {
        return $this->isIdentifier;
    }

    public function setIsIdentifier(bool $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    public function setAttributeName(string $attributeName): self
    {
        $this->attributeName = $attributeName;

        return $this;
    }
}
