<?php

namespace Mapado\RestClientSdk\Tests\Model\Issue46;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="sections")
 */
class Section
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
     * @Rest\Attribute(name="title", type="string")
     */
    private $title;

    /**
     * @Rest\OneToMany(name="articleList", targetEntity="Article")
     */
    private $articleList = [];

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
     * @return Section
     */
    public function setIri($iri)
    {
        $this->iri = $iri;

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function addArticle(Article $article)
    {
        $this->articleList[] = $article;
    }

    public function getArticleList()
    {
        return $this->articleList;
    }
}
