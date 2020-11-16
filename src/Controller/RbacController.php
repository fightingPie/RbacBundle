<?php

namespace SymfonyRbac\Controller;

use SymfonyRbac\Services\RbacManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RbacController
 * @package SymfonyRbac\Controller
 * @Route("/rbac", name="rbac_")
 */
class RbacController extends AbstractController
{
    /**
     * @var RbacManager
     */
    private $rbacManager;

    /**
     * RbacController constructor.
     * @param RbacManager $rbacManager
     */
    public function __construct(RbacManager $rbacManager)
    {
        $this->rbacManager = $rbacManager;
    }


    /**
     * @Route("/routes",name="routes", methods={"GET"})
     * @return JsonResponse
     */
    public function routesAction()
    {
        $routeList = $this->get('router')->getRouteCollection()->all();
        if (empty($routeList)) {
            throw new NotFoundHttpException();
        }

        $list = array_keys($routeList);
        return $this->json($list);
    }

    /**
     * @Route("/mapping/roles",name="roles_mapping", methods={"GET"})
     * @return JsonResponse
     */
    public function roleMappingAction()
    {
        $roles = $this->rbacManager->getRoles();
        if (empty($roles)) {
            throw new NotFoundHttpException();
        }
        $list = array_values($roles);
        return $this->json($list);
    }


    /**
     * @Route("/mapping/permissions",name="permissions_mapping", methods={"GET"})
     * @return JsonResponse
     */
    public function permissionMappingAction()
    {
        $permissions = $this->rbacManager->getPermissions();
        if (empty($permissions)) {
            throw new NotFoundHttpException();
        }
        $list = array_values($permissions);
        return $this->json($list);
    }
}