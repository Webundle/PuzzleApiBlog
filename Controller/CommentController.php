<?php

namespace Puzzle\Api\BlogBundle\Controller;

use JMS\Serializer\SerializerInterface;
use Puzzle\Api\BlogBundle\Entity\Article;
use Puzzle\Api\BlogBundle\Entity\Comment;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\ErrorFactory;
use Puzzle\OAuthServerBundle\Service\Repository;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Article API
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class CommentController extends BaseFOSRestController
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
        $this->fields = ['authorName', 'authorEmail', 'content', 'visible', 'article', 'parent'];
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/comments")
     */
    public function getBlogCommentsAction(Request $request) {
        $query = $request->query;
        
        $query = Utils::blameRequestQuery($query, $this->getUser());
        $response = $this->repository->filter($query, Comment::class, $this->connection);
        
        return $this->handleView(FormatUtil::formatView($request, $response));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/comments/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
     */
    public function getBlogCommentAction(Request $request, Comment $comment) {
        if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()){
            return $this->handleView($this->errorFactory->accessDenied($request));
        }
        
        return $this->handleView(FormatUtil::formatView($request, ['resources' => $comment]));
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/comments")
	 */
	public function postBlogCommentAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['article'] = $em->getRepository(Article::class)->find($data['article']);
	    
	    /** @var Comment $comment */
	    $comment = Utils::setter(new Comment(), $this->fields, $data);
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->persist($comment);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $comment]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/comments/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
	 */
	public function putBlogCommentAction(Request $request, Comment $comment) {
	    if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    
	    /** @var Comment $comment */
	    $comment = Utils::setter($comment, $this->fields, $data);
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/comments/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
	 */
	public function deleteBlogCommentAction(Request $request, Comment $comment) {
	    if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->remove($comment);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}