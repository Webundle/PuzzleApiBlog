<?php 

namespace Puzzle\Api\BlogBundle\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Puzzle\Api\BlogBundle\Entity\Archive;
use Puzzle\Api\BlogBundle\Event\ArticleEvent;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class ArticleListener
{
	/**
	 * @var EntityManagerInterface
	 */
	private $em;
	
	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
	}
	
	/**
	 * Create article archive
	 * 
	 * @param ArticleEvent $event
	 */
	public function onCreateArticle(ArticleEvent $event) {
	    $article = $event->getArticle();
	    
	    $now = new \DateTime();
	    $archive = $this->em->getRepository(Archive::class)->findOneBy([
	        'month' => (int) $now->format("m"),
	        'year' => $now->format("Y")
	    ]);
	    
	    if ($archive === null) {
	        $archive = new Archive();
	        $archive->setMonth((int) $now->format("m"));
	        $archive->setYear($now->format("Y"));
	        
	        $this->em->persist($archive);
	    }
	    
	    $article->setArchive($archive);
	    $this->em->flush();
	    
	    return;
	}
	
	/**
	 * Delete article archive
	 * 
	 * @param ArticleEvent $event
	 */
	public function onDeleteAction(ArticleEvent $event) {
	    $article = $event->getArticle();
	    $archive = $article->getArchive();
	    
	    if ($archive->getArticles()->count() == 1) {
	        $this->em->remove($archive);
	        $this->em->flush($archive);
	    }
	    
	    return;
	}
}

?>
