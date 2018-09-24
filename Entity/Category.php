<?php
namespace Puzzle\Api\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Doctrine\Common\Collections\Collection;
use Puzzle\OAuthServerBundle\Traits\Nameable;
use Puzzle\OAuthServerBundle\Traits\Describable;
use Puzzle\OAuthServerBundle\Traits\Pictureable;
use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;
use Knp\DoctrineBehaviors\Model\Sluggable\Sluggable;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 * @ORM\Table(name="blog_category")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("blog_category")
 * @Hateoas\Relation(
 * 		name = "self", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_category", 
 * 			parameters = {"id" = "expr(object.getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 * 		name = "articles", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_articles", 
 * 			parameters = {"filter" = "category==expr(object.getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "parent",
 *     embedded = "expr(object.getParent())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getParent() === null)"),
 *     href = @Hateoas\Route(
 * 			"get_blog_category", 
 * 			parameters = {"id" = "expr(object.getParent().getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 *     name = "childs",
 *     embedded = "expr(object.getChilds())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getChilds() === null)")
 * ))
 */
class Category
{
    use PrimaryKeyable,
        Timestampable,
        Nameable,
        Describable,
        Pictureable,
        Blameable,
        Sluggable;
    
    /**
     * @var string
     * @ORM\Column(name="slug", type="string")
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $slug;
    
    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
     */
    private $articles;
    
    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $childs;
    
    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="childs")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;
    
    /**
     * Constructor
     */
    public function __construct() {
    	$this->articles = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function getSluggableFields() {
        return [ 'name' ];
    }
    
    public function generateSlugValue($values) {
        return implode('-', $values);
    }
    
    public function setArticles(Collection $articles) :self {
        $this->articles = $articles;
        return $this;
    }
    
    public function addArticle(Article $article) : self {
        if ($this->articles === null || $this->articles->contains($article) === false) {
            $this->articles->add($article);
        }
        
        return $this;
    }

    public function removeArticle(Article $article) :self {
        $this->articles->removeElement($article);
        return $this;
    }

    public function getArticles(){
        return $this->articles;
    }

    public function setParent(Category $parent = null) {
        $this->parent = $parent;
        return $this;
    }

    public function getParent() :?self {
        return $this->parent;
    }

    public function addChild(Category $child) :self {
        $this->childs[] = $child;
        return $this;
    }

    public function removeChild(Category $child) :self {
        $this->childs->removeElement($child);
        return $this;
    }

    public function getChilds() :?Collection {
        return $this->childs;
    }
}
