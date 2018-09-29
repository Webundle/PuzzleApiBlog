<?php

namespace Puzzle\Api\BlogBundle\Controller;

use Puzzle\Api\BlogBundle\PuzzleApiBlogEvents;
use Puzzle\Api\BlogBundle\Entity\Article;
use Puzzle\Api\BlogBundle\Entity\Category;
use Puzzle\Api\BlogBundle\Event\ArticleEvent;
use Puzzle\Api\MediaBundle\PuzzleApiMediaEvents;
use Puzzle\Api\MediaBundle\Event\FileEvent;
use Puzzle\Api\MediaBundle\Util\MediaUtil;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class ArticleController extends BaseFOSRestController
{
    public function __construct(){
        parent::__construct();
        $this->fields = ['name', 'tags', 'enableComments', 'description', 'visible', 'category', 'author', 'flash', 'flashExpiresAt'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/articles")
	 */
	public function getBlogArticlesAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    
	    /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
	    $repository = $this->get('papis.repository');
	    $response = $repository->filter($query, Article::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function getBlogArticleAction(Request $request, Article $article) {
	    if ($article->getCreatedBy()->getId() !== $this->getUser()->getId()){
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, $article));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/articles")
	 */
	public function postBlogArticleAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['category'] = $em->getRepository(Category::class)->find($data['category']);

	    /** @var Puzzle\Api\BlogBundle\Entity\Article $article */
	    $article = Utils::setter(new Article(), $this->fields, $data);
	    
	    $em->persist($article);
	    $em->flush();
	    
	    /** @var Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
	    $dispatcher = $this->get('event_dispatcher');
	    /*  Archive article */
	    $dispatcher->dispatch(PuzzleApiBlogEvents::ARTICLE_CREATE, new ArticleEvent($article));
	    /* Set article picture */
	    if (isset($data['picture']) && $data['picture']) {
	        $dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Article::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($article){$article->setPicture($filename);}
	        ]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, $article));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function putBlogArticleAction(Request $request, Article $article) {
	    $user = $this->getUser();
	    
	    if ($article->getCreatedBy()->getId() !== $user->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	   
	    if (isset($data['category']) && $data['category'] !== null) {
	        $data['category'] = $em->getRepository(Category::class)->find($data['category']);
	    }

	    /** @var Puzzle\Api\BlogBundle\Entity\Article $article */
	    $article = Utils::setter($article, $this->fields, $data);
	    
	    /** @var Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
	    $dispatcher = $this->get('event_dispatcher');
	    
	    /* Update article picture */
	    if (isset($data['picture']) && $data['picture'] !== $article->getPicture()) {
	        $dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Article::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($article){$article->setPicture($filename);}
	        ]));
	    }
	    
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $article));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function deleteBlogArticleAction(Request $request, Article $article) {
	    $user = $this->getUser();
	    
	    if ($article->getCreatedBy()->getId() !== $user->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    /** @var Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
	    $dispatcher = $this->get('event_dispatcher');
	    /* Unarchive article */
	    $dispatcher->dispatch(PuzzleApiBlogEvents::ARTICLE_DELETE, new ArticleEvent($article));
	    
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->remove($article);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
}