<?php

namespace Puzzle\Api\BlogBundle\Controller;

use JMS\Serializer\SerializerInterface;
use Puzzle\Api\BlogBundle\Entity\Category;
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
class CategoryController extends BaseFOSRestController
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
        $this->fields = ['name', 'description', 'parent'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/categories")
	 */
	public function getBlogCategoriesAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    $response = $this->repository->filter($query, Category::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverte("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function getBlogCategoryAction(Request $request, Category $category) {
	    if ($category->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $category]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/categories")
	 */
	public function postBlogCategoryAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['parent'] = isset($data['parent']) && $data['parent'] ? $em->getRepository(Category::class)->find($data['parent']) : null;
	    
	    /** @var Category $category */
	    $category = Utils::setter(new Category(), $this->fields, $data);
	    
	    $em->persist($category);
	    $em->flush();
	    
	    /* Category picture listener */
	    if (isset($data['picture']) && $data['picture']){
	        $this->dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Category::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($category){$category->setPicture($filename);}
	        ]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $category]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverte("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function putBlogCategoryAction(Request $request, Category $category) {
	    $user = $this->getUser();
	    
	    if ($category->getCreatedBy()->getId() !== $user->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    if (isset($data['parent']) && $data['parent'] !== null) {
	        $data['parent'] = $em->getRepository(Category::class)->find($data['parent']);
	    }
	    
	    /** @var Category $category */
	    $category = Utils::setter($category, $this->fields, $data);
	    
	    /* Article picture listener */
	    if (isset($data['picture']) && $data['picture'] !== $category->getPicture()) {
	        $this->dispatcher->dispatch(PuzzleApiMediaEvents::MEDIA_COPY_FILE, new FileEvent([
	            'path'     => $data['picture'],
	            'folder'   => $data['uploadDir'] ?? MediaUtil::extractFolderNameFromClass(Category::class),
	            'user'     => $this->getUser(),
	            'closure'  => function($filename) use ($category){$category->setPicture($filename);}
	        ]));
	    }
	    
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/categories/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverte("category", class="PuzzleApiBlogBundle:Category")
	 */
	public function deleteBlogCategoryAction(Request $request, Category $category) {
	    if ($category->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->remove($category);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}