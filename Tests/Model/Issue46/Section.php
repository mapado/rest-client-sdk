<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Tests\Model\Issue46;

use Mapado\RestClientSdk\Mapping\Attributes as Rest;

#[Rest\Entity(key: 'sections')]
class Section
{
    #[
        Rest\Id,
        Rest\Attribute(name: '@id', type: 'string')
    ]
    private $iri;

    #[Rest\Attribute(name: 'id', type: 'string')]
    private $id;

    #[Rest\Attribute(name: 'title', type: 'string')]
    private $title;

    #[Rest\OneToMany(name: 'articleList', targetEntity: Article::class)]
    private $articleList = [];

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
     * @return Section
     */
    public function setIri($iri)
    {
        $this->iri = $iri;

        return $this;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function addArticle(Article $article): void
    {
        $this->articleList[] = $article;
    }

    public function getArticleList()
    {
        return $this->articleList;
    }
}
