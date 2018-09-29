<?php

namespace Puzzle\Api\BlogBundle\Controller;

use Puzzle\Api\BlogBundle\Entity\Category;
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
class CategoryController extends BaseFOSRestController
{
    public function __construct(){
        parent::__construct();
        $this->fields = ['name', 'description', 'parent'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/categories")
	 */
	public function getBlogCategoriesAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    
	    /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
	    $repository = $this->get('papis.repository');
	    $response = $repository->filter($query, Category::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function getBlogCategoryAction(Request $request, Category $category) {
	    if ($category->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, $category));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/categories")
	 */
	public function postBlogCategoryAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['parent'] = isset($data['parent']) && $data['parent'] ? $em->getRepository(Category::class)->find($data['parent']) : null;
	    
	    /** @var Puzzle\Api\BlogBundle\Entity\Category $category */
	    $category = Utils::setter(new Category(), $this->fields, $data);
	    
	    $em->persist($category);
	    $em->flush();
	    
	    /* Category picture listener */
	    if (isset($data['picture']) && $data['picture']){
	        /** @var Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
	        $dispatcher = $this->get('event_dispatcher');
	        $dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Category::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($category){$category->setPicture($filename);}
	        ]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, $category));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function putBlogCategoryAction(Request $request, Category $category) {
	    $user = $this->getUser();
	    
	    if ($category->getCreatedBy()->getId() !== $user->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    
	    $data = $request->request->all();
	    if (isset($data['parent']) && $data['parent'] !== null) {
	        $data['parent'] = $em->getRepository(Category::class)->find($data['parent']);
	    }
	    
	    /** @var Puzzle\Api\BlogBundle\Entity\Category $category */
	    $category = Utils::setter($category, $this->fields, $data);
	    
	    /* Article picture listener */
	    if (isset($data['picture']) && $data['picture'] !== $category->getPicture()) {
	        /** @var Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
	        $dispatcher = $this->get('event_dispatcher');
	        $dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Category::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($category){$category->setPicture($filename);}
	        ]));
	    }
	    
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $category));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function deleteBlogCategoryAction(Request $request, Category $category) {
	    if ($category->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	        $errorFactory = $this->get('papis.error_factory');
	        return $this->handleView($errorFactory->badRequest($request));
	    }
	    
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->remove($category);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
}