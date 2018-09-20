<?php
namespace Puzzle\Api\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Puzzle\OAuthServerBundle\Traits\Nameable;
use Puzzle\OAuthServerBundle\Traits\Describable;
use Puzzle\OAuthServerBundle\Traits\Pictureable;
use Puzzle\OAuthServerBundle\Traits\Taggable;
use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
use Knp\DoctrineBehaviors\Model\Sluggable\Sluggable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;

/**
 * Article
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 * @ORM\Table(name="blog_article")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("blog_article")
 * @Hateoas\Relation(
 * 		name = "self", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_article", 
 * 			parameters = {"id" = "expr(object.getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "category",
 *     embedded = "expr(object.getCategory())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getCategory() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_category", 
 * 			parameters = {"id" = "expr(object.getCategory().getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "archive",
 *     embedded = "expr(object.getArchive())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getArchive() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_archive", 
 * 			parameters = {"id" = "expr(object.getArchive().getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "comments",
 *     embedded = "expr(object.getComments())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getComments() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_comments", 
 * 			parameters = {"filter" = "article==expr(object.getId())"},
 * 			absolute = true,
 * ))
 */
class Article
{
    use PrimaryKeyable,
        Timestampable,
        Nameable,
        Blameable,
        Sluggable,
        Describable,
        Pictureable,
        Taggable;
    
    /**
     * @ORM\Column(name="short_description", type="string", length=255, nullable=true)
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     */
    private $shortDescription;
    
    /**
     * @ORM\Column(name="slug", type="string", length=255)
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $slug;
    
    /**
     * @ORM\Column(name="enable_comments", type="boolean")
     * @var boolean
     * @JMS\Expose
     * @JMS\Type("boolean")
     */
    private $enableComments;
    
    /**
     * @ORM\Column(name="visible", type="boolean")
     * @var boolean
     * @JMS\Expose
     * @JMS\Type("boolean")
     */
    private $visible;
    
    /**
     * @ORM\Column(name="author", type="string")
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     */
    private $author;
   
    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    private $category;
    
    /**
     * @ORM\ManyToOne(targetEntity="Archive", inversedBy="articles")
     * @ORM\JoinColumn(name="archive_id", referencedColumnName="id")
     */
    private $archive;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="article")
     */
    private $comments;
    
    /**
     * Constructor
     */
    public function __construct() {
    	$this->comments = new \Doctrine\Common\Collections\ArrayCollection();
    	$this->visible = true;
    	$this->enableComments = false;
    }
    
    public function getSluggableFields()
    {
        return [ 'name' ];
    }
    
    public function generateSlugValue($values)
    {
        return implode('-', $values);
    }
    
    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setShortDescription(){
        $this->shortDescription = strlen($this->description) > 100 ? 
                                  mb_strimwidth($this->description, 0, 100, '...') : $this->description;
        return $this;
    }

    public function getShortDescription() :?string {
        return $this->shortDescription;
    }

    public function setEnableComments($enableComments) :self {
        $this->enableComments = $enableComments;
        return $this;
    }

    public function getEnableComments() :?bool {
        return $this->enableComments;
    }
    
    public function setVisible(bool $visible) :self {
        $this->visible = $visible;
        return $this;
    }
    
    public function isVisible() :?bool {
        return $this->visible;
    }
    
    public function setAuthor($author) {
        $this->author = $author;
        return $this;
    }
    
    public function getAuthor() {
        return $this->author;
    }
    
    public function setCategory(Category $category) :self {
        $this->category = $category;
        return $this;
    }

    public function getCategory() :?Category {
        return $this->category;
    }
    
    public function setArchive(Archive $archive) :self {
        $this->archive = $archive;
        return $this;
    }
    
    public function getArchive() :?Archive {
        return $this->archive;
    }

    public function addComment(Comment $comment) :self {
        if ($this->comments === null || $this->comments->contains($comment) === false) {
            $this->comments->add($comment);
        }
        
        return $this;
    }

    public function removeComment(Comment $comment) :self {
        $this->comments->removeElement($comment);
    }

    public function getComments() :?Collection {
        return $this->comments;
    }
    
    public function __toString(){
        return $this->getName();
    }
}
