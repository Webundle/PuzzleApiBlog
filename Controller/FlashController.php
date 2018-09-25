<?php

namespace Puzzle\Api\BlogBundle\Controller;

use JMS\Serializer\SerializerInterface;
use Puzzle\Api\BlogBundle\Entity\Flash;
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
class FlashController extends BaseFOSRestController
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
        $this->fields = ['content', 'expiredAt'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/flashes")
	 */
	public function getBlogFlashesAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    $response = $this->repository->filter($query, Flash::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/flashes/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("flash", class="PuzzleApiBlogBundle:Flash")
	 */
	public function getBlogFlashAction(Request $request, Flash $flash) {
	    if ($flash->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $flash]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/flashes")
	 */
	public function postBlogFlashAction(Request $request) {
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    
	    $data = $request->request->all();
	    $data['parent'] = isset($data['parent']) && $data['parent'] ? $em->getRepository(Flash::class)->find($data['parent']) : null;
	    
	    /** @var Flash $flash */
	    $flash = Utils::setter(new Flash(), $this->fields, $data);
	    
	    $em->persist($flash);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $flash]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/flashes/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("flash", class="PuzzleApiBlogBundle:Flash")
	 */
	public function putBlogFlashAction(Request $request, Flash $flash) {
	    $user = $this->getUser();
	    
	    if ($flash->getCreatedBy()->getId() !== $user->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    /** @var Flash $flash */
	    $flash = Utils::setter($flash, $this->fields, $data);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/flashes/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("flash", class="PuzzleApiBlogBundle:Flash")
	 */
	public function deleteBlogFlashAction(Request $request, Flash $flash) {
	    if ($flash->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $em = $this->doctrine->getManager($this->connection);
	    $em->remove($flash);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}