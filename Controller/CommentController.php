<?php

namespace Puzzle\Api\BlogBundle\Controller;

use Puzzle\Api\BlogBundle\Entity\Article;
use Puzzle\Api\BlogBundle\Entity\Comment;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class CommentController extends BaseFOSRestController
{
    public function __construct() {
        parent::__construct();
        $this->fields = ['authorName', 'authorEmail', 'content', 'visible', 'article', 'parent'];
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/comments")
     */
    public function getBlogCommentsAction(Request $request) {
        $query = Utils::blameRequestQuery($request->query, $this->getUser());
        
        /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
        $repository = $this->get('papis.repository');
        $response = $repository->filter($query, Comment::class, $this->connection);
        
        return $this->handleView(FormatUtil::formatView($request, $response));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/comments/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
     */
    public function getBlogCommentAction(Request $request, Comment $comment) {
        if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }
        
        return $this->handleView(FormatUtil::formatView($request, $comment));
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/comments")
	 */
	public function postBlogCommentAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['article'] = $em->getRepository(Article::class)->find($data['article']);
	    $data['parent'] = isset($data['parent']) && $data['parent'] ? $em->getRepository(Comment::class)->find($data['parent']) : null;
	    
	    /** @var Puzzle\Api\BlogBundle\Entity\Comment $comment */
	    $comment = Utils::setter(new Comment(), $this->fields, $data);
	    
	    $em->persist($comment);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $comment));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/comments/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
	 */
	public function putBlogCommentAction(Request $request, Comment $comment) {
	    if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    /** @var Comment $comment */
	    $comment = Utils::setter($comment, $this->fields, $request->request->all());
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $comment));
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/comments/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("comment", class="PuzzleApiBlogBundle:Comment")
	 */
	public function deleteBlogCommentAction(Request $request, Comment $comment) {
	    if ($comment->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->remove($comment);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
}