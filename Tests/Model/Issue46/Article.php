<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Issue46;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

#[Rest\Entity(key: 'articles')]
class Article
{
    #[
        Rest\Id,
        Rest\Attribute(name: '@id', type: 'string')
    ]
    private $iri;

    /**
     * @Rest\Attribute(name="id", type="string")
     */
    #[Rest\Attribute(name: 'id', type: 'string')]
    private $id;

    /**
     * @Rest\ManyToOne(name="section", targetEntity="Section")
     */
    #[Rest\ManyToOne(name: 'section', targetEntity: 'Section')]
    private $section;

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Getter for iri
     *
     * @return string
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * Setter for iri
     *
     * @param string $iri
     *
     * @return Article
     */
    public function setIri($iri)
    {
        $this->iri = $iri;

        return $this;
    }

    public function setSection(Section $section): void
    {
        $this->section = $section;

        $this->section->addArticle($this);
    }

    public function getSection()
    {
        return $this->section;
    }
}
