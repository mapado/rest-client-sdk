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
     * @Rest\Attribute(name="title", type="string")
     */
    private $title;

    /**
     * @Rest\ManyToOne(name="tag", targetEntity="Tag")
     */
    private $tag;

    /**
     * @Rest\OneToMany(name="tagList", targetEntity="Tag")
     */
    private $tagList;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
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

    /**
     * Getter for tagList
     *
     * @return array
     */
    public function getTagList()
    {
        return $this->tagList;
    }

    /**
     * Setter for tagList
     */
    public function setTagList($tagList)
    {
        $this->tagList = $tagList;

        return $this;
    }
}
