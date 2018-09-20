<?php
namespace Puzzle\Api\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Knp\DoctrineBehaviors\Model\Blameable\Blameable;
use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
use Doctrine\Common\Collections\Collection;

/**
 * Article Comment
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 * @ORM\Table(name="blog_comment")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("blog_comment")
 * @Hateoas\Relation(
 *     name = "parent",
 *     embedded = "expr(object.getParent())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getParent() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_comment", 
 * 			parameters = {"id" = "expr(object.getParent().getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "childs",
 *     embedded = "expr(object.getChilds())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getChilds() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_comments", 
 * 			parameters = {"filter" = "parent==expr(object.getParent().getId())"},
 * 			absolute = true,
 * ))
 */
class Comment
{
    use PrimaryKeyable,
        Timestampable,
        Blameable;
    
    /**
     * @var string
     * @ORM\Column(name="author_name", type="string")
     * @JMS\Expose
     * @JMS\Type("string")
     */
     private $authorName;
     
     /**
      * @var string
      * @ORM\Column(name="author_email", type="string", nullable=true)
      * @JMS\Expose
      * @JMS\Type("string")
      */
     private $authorEmail;
    
    /**
     * @var string
     * @ORM\Column(name="content", type="text")
     * @JMS\Expose
     * @JMS\Type("string")
     */
    private $content;

    /**
     * @var bool
     * @ORM\Column(name="visible", type="boolean")
     * @JMS\Expose
     * @JMS\Type("boolean")
     */
    private $visible;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="comments")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    private $article;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="parent")
     */
    private $childs;
    
    /**
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="childs")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;
    
    public function __construct() {
        $this->visible = false;
    }
    
    public function setAuthorName($authorName) :self {
        $this->authorName = $authorName;
        return $this;
    }
    
    public function getAuthorName() :?string {
        return $this->authorName;
    }
    
    public function setAuthorEmail($authorEmail) :self {
        $this->authorEmail = $authorEmail;
        return $this;
    }
    
    public function getAuthorEmail() :? string {
        return $this->authorEmail;
    }
    
    public function setContent($content) : self {
        $this->content = $content;
        return $this;
    }

    public function getContent() :? string {
        return $this->content;
    }

    public function setVisible($visible) : self {
        $this->visible = $visible;
        return $this;
    }

    public function isVisible() :? bool {
        return $this->visible;
    }

    
    public function setArticle(Article $article = null){
        $this->article = $article;
        return $this;
    }

    public function getArticle() : Article {
        return $this->article;
    }
    
    public function setParent(Comment $parent = null) {
        $this->parent = $parent;
        return $this;
    }
    
    public function getParent() :?self {
        return $this->parent;
    }
    
    public function addChild(Comment $child) :self {
        $this->childs[] = $child;
        return $this;
    }
    
    public function removeChild(Comment $child) :self {
        $this->childs->removeElement($child);
        return $this;
    }
    
    public function getChilds() :?Collection {
        return $this->childs;
    }
}
