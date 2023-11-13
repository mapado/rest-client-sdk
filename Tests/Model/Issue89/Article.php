<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Issue89;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

#[Rest\Entity(key: 'articles')]
class Article
{
    /**
     * @var int
     */
    #[Rest\Attribute(name: 'id', type: 'int')]
    public $id;

    #[Rest\Attribute(name: 'title', type: 'string')]
    public $title;
}
