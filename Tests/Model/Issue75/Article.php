<?php

namespace Mapado\RestClientSdk\Tests\Model\Issue75;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="articles")
 */
class Article
{
    /**
     * @Rest\Id
     * @Rest\Attribute(name="@id", type="string")
     */
    private $iri;

    /**
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * @Rest\ManyToOne(name="tag", targetEntity="Tag")
     */
    private $tag;

    public function setId($id)
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

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }
}
