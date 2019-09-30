<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Issue90;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="plop")
 */
class WithIdInt
{
    /**
     * @var int
     *
     * @Rest\Id
     * @Rest\Attribute(name="id", type="int")
     */
    private $id;

    /**
     * @Rest\Attribute(name="title", type="string")
     */
    private $title;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
