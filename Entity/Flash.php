<?php

namespace Puzzle\Api\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 * @ORM\Table(name="blog_flash")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("article")
 * @Hateoas\Relation(
 * 		name = "self", 
 * 		href = @Hateoas\Route(
 * 			"get_blog_flash", 
 * 			parameters = {"id" = "expr(object.getId())"},
 * 			absolute = true,
 * ))
 */
class Flash
{
    use PrimaryKeyable, Blameable;

    /**
     * @var \DateTime
     * @ORM\Column(name="expired_at", type="datetime", nullable=true)
     * @JMS\Expose
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $expiredAt;

    /**
     * @var string
     * @ORM\Column(name="content", type="text")
     * @JMS\Expose
     * @JMS\Type("string")
     */
    private $content;

    public function getId() :? string{
        return $this->id;
    }

    public function setExpiredAt(\DateTime $expiredAt) : self {
        $this->expiredAt = $expiredAt;
        return $this;
    }

    public function getExpiredAt() :? \DateTime {
        return $this->expiredAt;
    }

    public function setContent($content) : self {
        $this->content = $content;
        return $this;
    }
    
    public function getContent() :? string {
        return $this->content;
    }
}

