<?php 

namespace Puzzle\Api\BlogBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Puzzle\Api\BlogBundle\Entity\Article;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class ArticleEvent extends Event
{
	/**
	 * @var Article
	 */
	private $article;
	
	/**
	 * @var array
	 */
	private $data;
	
	public function __construct(Article $article, array $data = null) {
	    $this->article= $article;
		$this->data = $data;
	}
	
	public function getArticle() {
		return $this->article;
	}
	
	public function getData() {
	    return $this->data;
	}
}

?>