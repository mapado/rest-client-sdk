<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Mapping;

/**
 * Class Relation
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class Relation
{
    public const MANY_TO_ONE = 'ManyToOne';
    public const ONE_TO_MANY = 'OneToMany';

    /**
     * @param class-string $targetEntity
     */
    public function __construct(
        private string $serializedKey,
        private string $type,
        private string $targetEntity,
    ) {
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

    public function isOneToMany(): bool
    {
        return self::ONE_TO_MANY === $this->getType();
    }

    public function isManyToOne(): bool
    {
        return self::MANY_TO_ONE === $this->getType();
    }

    /**
     * @return class-string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    /**
     * @param class-string $targetEntity
     */
    public function setTargetEntity(string $targetEntity): self
    {
        $this->targetEntity = $targetEntity;

        return $this;
    }
}
