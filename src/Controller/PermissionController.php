<?php

namespace SymfonyRbac\Controller;

use Exception;
use Knp\Component\Pager\PaginatorInterface;
use SymfonyRbac\Rbac\Item;
use SymfonyRbac\Rbac\Permission;
use SymfonyRbac\Repository\AuthItemRepository;
use SymfonyRbac\Services\RbacManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class PermissionController
 * @package SymfonyRbac\Controller
 * @Route("/rbac/permissions", name="rbac_permissions_")
 */
class PermissionController extends BaseController
{
    /**
     * @var RbacManager
     */
    private $rbacManager;
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var AuthItemRepository
     */
    private $authItemRepository;

    /**
     * RoleController constructor.
     * @param RbacManager $rbacManager
     * @param AuthItemRepository $authItemRepository
     */
    public function __construct(RbacManager $rbacManager, AuthItemRepository $authItemRepository)
    {
        $this->rbacManager = $rbacManager;

        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $this->authItemRepository = $authItemRepository;
    }


    /**
     * @Route(name="list", methods={"GET"})
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {

        $page = $request->query->getInt('page', 1);
        $pageSize = $request->query->getInt('page_size', 10);

        $option = [];

        $category = $request->get('category', null);
        if ($category) {
            $option[] = [
                'key' => 'category',
                'value' => $category
            ];
        }

        $name = $request->get('name', null);
        if ($name) {
            $option[] = [
                'key' => 'name',
                'value' => $name
            ];
        }

        $status = $request->get('status', null);
        if ($name) {
            $option[] = [
                'key' => 'status',
                'value' => $status
            ];
        }

        $query = $this->authItemRepository->getItems(Item::TYPE_PERMISSION, $option);
        $permissionsList = $paginator->paginate($query, $page, $pageSize);
        if (empty($permissionsList->getItems())) {
            throw new NotFoundHttpException();
        }

        return $this->paginatedJson(
            $permissionsList->getItems(),
            $permissionsList->getCurrentPageNumber(),
            $permissionsList->getItemNumberPerPage(),
            $permissionsList->getTotalItemCount()
        );
    }


    /**
     * @Route("/{permissionName}",name="view", methods={"GET"})
     * @param string $permissionName
     * @return JsonResponse
     * @throws Exception
     */
    public function viewAction(string $permissionName)
    {
        $permission = $this->authItemRepository->findOneBy(['type' => Item::TYPE_PERMISSION, 'name' => $permissionName]);
        if (empty($permission)) {
            throw new NotFoundHttpException();
        }
        return $this->json($permission);
    }

    /**
     * @Route(name="add", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function addAction(Request $request)
    {
        $raw = $request->getContent();

        $roleObject = $this->serializer->deserialize($raw, Permission::className(), 'json');

        $this->rbacManager->add($roleObject);

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{permissionName}",name="update", methods={"PATCH"})
     * @param Request $request
     * @param string $permissionName
     * @return JsonResponse
     * @throws Exception
     */
    public function editAction(Request $request, string $permissionName)
    {
        $permission = $this->rbacManager->getPermission($permissionName);
        if (empty($permission)) {
            throw new NotFoundHttpException();
        }

        $raw = $request->getContent();

        $permission = $this->serializer->deserialize($raw, Permission::className(), 'json');

        $this->rbacManager->update($permissionName, $permission);

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{permissionName}",name="delete", methods={"DELETE"})
     * @param string $permissionName
     * @return JsonResponse
     * @throws Exception
     */
    public function removeAction(string $permissionName)
    {
        $permission = $this->rbacManager->getPermission($permissionName);

        if (empty($permission)) {
            throw new NotFoundHttpException();
        }

        $this->rbacManager->remove($permission);

        return $this->json(['message' => 'OK']);

    }


}