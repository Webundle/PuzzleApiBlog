<?php

namespace Puzzle\Api\BlogBundle\Controller;

use JMS\Serializer\SerializerInterface;
use Puzzle\Api\BlogBundle\PuzzleApiBlogEvents;
use Puzzle\Api\BlogBundle\Entity\Article;
use Puzzle\Api\BlogBundle\Entity\Category;
use Puzzle\Api\BlogBundle\Event\ArticleEvent;
use Puzzle\Api\MediaBundle\PuzzleApiMediaEvents;
use Puzzle\Api\MediaBundle\Event\FileEvent;
use Puzzle\Api\MediaBundle\Util\MediaUtil;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\ErrorFactory;
use Puzzle\OAuthServerBundle\Service\Repository;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class ArticleController extends BaseFOSRestController
{
    /**
     * @param RegistryInterface         $doctrine
     * @param Repository                $repository
     * @param SerializerInterface       $serializer
     * @param EventDispatcherInterface  $dispatcher
     * @param ErrorFactory              $errorFactory
     */
    public function __construct(
        RegistryInterface $doctrine,
        Repository $repository,
        SerializerInterface $serializer,
        EventDispatcherInterface $dispatcher,
        ErrorFactory $errorFactory
    ){
        parent::__construct($doctrine, $repository, $serializer, $dispatcher, $errorFactory);
        $this->fields = ['name', 'tags', 'enableComments', 'description', 'visible', 'category', 'author'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/articles")
	 */
	public function getBlogArticlesAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    $response = $this->repository->filter($query, Article::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function getBlogArticleAction(Request $request, Article $article) {
	    if ($article->getCreatedBy()->getId() !== $this->getUser()->getId()){
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $article]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/articles")
	 */
	public function postBlogArticleAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['category'] = $em->getRepository(Category::class)->find($data['category']);
	    
	    /** @var Article $article */
	    $article = Utils::setter(new Article(), $this->fields, $data);
	    
	    $em->persist($article);
	    $em->flush();
	    
	    /* Archive article listener */
	    $this->dispatcher->dispatch(PuzzleApiBlogEvents::ARTICLE_CREATE, new ArticleEvent($article));
	    /* Article picture listener */
	    if (isset($data['picture']) && $data['picture']) {
	        $this->dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Article::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($article){$article->setPicture($filename);}
	        ]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $article]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function putBlogArticleAction(Request $request, Article $article) {
	    $user = $this->getUser();
	    
	    if ($article->getCreatedBy()->getId() !== $user->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    if (isset($data['category']) && $data['category'] !== null) {
	        $data['category'] = $em->getRepository(Category::class)->find($data['category']);
	    }
	    
	    /** @var Article $article */
	    $article = Utils::setter($article, $this->fields, $data);
	    
	    /* Article picture listener */
	    if (isset($data['picture']) && $data['picture'] !== $article->getPicture()) {
	        $this->dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Article::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($article){$article->setPicture($filename);}
	        ]));
	    }
	    
	    $em->merge($article);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/articles/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("article", class="PuzzleApiBlogBundle:Article")
	 */
	public function deleteBlogArticleAction(Request $request, Article $article) {
	    $user = $this->getUser();
	    
	    if ($article->getCreatedBy()->getId() !== $user->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    /* Archive article listener */
	    $this->dispatcher->dispatch(PuzzleApiBlogEvents::ARTICLE_DELETE, new ArticleEvent($article));
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->remove($article);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}