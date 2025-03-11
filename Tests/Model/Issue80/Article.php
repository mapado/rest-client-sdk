<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Issue80;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

#[Rest\Entity(key: 'article')]
class Article
{
    /**
     * @var int
     */
    #[Rest\Id]
    #[Rest\Attribute(name: 'id', type: 'string')]
    private $id;

    #[Rest\Attribute(name: 'title', type: 'string')]
    private $title;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
