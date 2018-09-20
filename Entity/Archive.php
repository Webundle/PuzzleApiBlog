<?php

namespace Puzzle\Api\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;

/**
 * Article Archive
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 * @ORM\Table(name="blog_archive")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("article")
 * @Hateoas\Relation(
 * 		name = "self", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_archive", 
 * 			parameters = {"id" = "expr(object.getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 * 		name = "articles", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_articles", 
 * 			parameters = {"filter" = "archive==expr(object.getId())"},
 * 			absolute = true,
 * ))
 */
class Archive
{
    use PrimaryKeyable, Blameable;

    /**
     * @var int
     * @ORM\Column(name="month", type="integer")
     * @JMS\Expose
     * @JMS\Type("integer")
     */
    private $month;

    /**
     * @var int
     * @ORM\Column(name="year", type="integer")
     * @JMS\Expose
     * @JMS\Type("integer")
     */
    private $year;
    
    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="archive")
     */
    private $articles;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->articles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() :? string{
        return $this->id;
    }

    public function setMonth($month) : self {
        $this->month = $month;
        return $this;
    }

    public function getMonth() :? int {
        return $this->month;
    }

    public function setYear($year) : self {
        $this->year = $year;
        return $this;
    }
    
    public function getYear() :? int {
        return $this->year;
    }
    
    public function addArticle(Article $article) : self {
        $this->articles[] = $article;
        return $this;
    }
    
    public function removeArticle(Article $article) : self {
        $this->articles->removeElement($article);
        return $this;
    }
    
    public function getArticles(){
        return $this->articles;
    }
}

