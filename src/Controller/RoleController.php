<?php

namespace SymfonyRbac\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use SymfonyRbac\Entity\AuthItem;
use SymfonyRbac\Rbac\Item;
use SymfonyRbac\Rbac\Permission;
use SymfonyRbac\Repository\AuthItemRepository;
use SymfonyRbac\Services\RbacManager;
use SymfonyRbac\Utils\RolePost;
use SymfonyRbac\Utils\RoleStatusPost;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class RoleController
 * @package SymfonyRbac\Controller
 * @Route("/rbac/roles", name="rbac_roles_")
 */
class RoleController extends BaseController
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * RoleController constructor.
     * @param RbacManager $rbacManager
     * @param AuthItemRepository $authItemRepository
     */
    public function __construct(RbacManager $rbacManager, AuthItemRepository $authItemRepository)
    {
        $this->rbacManager = $rbacManager;
        $this->serializer = new Serializer([new ObjectNormalizer(), new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
        $this->authItemRepository = $authItemRepository;
        $this->entityManager = $rbacManager->getEm();
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
        $user = $request->get('user', null);
        if ($user) {
            $roles = $this->rbacManager->getRolesByUser($user);
            if ($roles) {
                $option[] = [
                    'key' => 'name',
                    'value' => array_keys($roles)
                ];
            }
        }


        $permission = $request->get('permission', null);
        /**
         * @var AuthItem $permissionItem
         * @var AuthItem $role
         */
        if ($permission) {
            $permissionItem = $this->authItemRepository->findOneBy(['name' => $permission, 'type' => Item::TYPE_PERMISSION]);
            if ($permissionItem) {
                $parentRoles = $permissionItem->getParent()->toArray();
                $option[] = [
                    'key' => 'name',
                    'value' => array_map(function ($role) {
                        return $role->getName();
                    }, $parentRoles)
                ];
            }
        }

        $name = $request->get('name', null);
        if ($name) {
            $option[] = [
                'key' => 'name',
                'value' => $name
            ];
        }

        $status = $request->get('status', null);
        if ($status) {
            $option[] = [
                'key' => 'status',
                'value' => $status
            ];
        }

        $query = $this->authItemRepository->getItems(Item::TYPE_ROLE, $option);
        $roleList = $paginator->paginate($query, $page, $pageSize);
        if (empty($roleList->getItems())) {
            throw new NotFoundHttpException();
        }

        return $this->paginatedJson(
            $roleList->getItems(),
            $roleList->getCurrentPageNumber(),
            $roleList->getItemNumberPerPage(),
            $roleList->getTotalItemCount()
        );
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
        $roleObject = $this->serializer->deserialize($raw, RolePost::className(), 'json');
        $this->entityManager->beginTransaction();
        try {
            $this->rbacManager->add($roleObject);
            if (isset($roleObject->child) && !empty($roleObject->child)){
                foreach ($roleObject->child as $child) {
                    $this->rbacManager->addChild($roleObject, $this->rbacManager->getPermission($child['name']));
                }
            }
            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{roleName}",name="view", methods={"GET"})
     * @param string $roleName
     * @return JsonResponse
     * @throws Exception
     */
    public function viewAction(string $roleName)
    {
        $role = $this->authItemRepository->findOneBy(['type' => Item::TYPE_ROLE, 'name' => $roleName]);
        if (empty($role)) {
            throw new NotFoundHttpException();
        }

        return $this->json($role);
    }

    /**
     * @Route("/{roleName}",name="update", methods={"PATCH"})
     * @param Request $request
     * @param string $roleName
     * @return JsonResponse
     * @throws Exception
     */
    public function editAction(Request $request, string $roleName)
    {
        $role = $this->rbacManager->getRole($roleName);
        if (empty($role)) {
            throw new NotFoundHttpException();
        }
        $raw = $request->getContent();
        $roleObject = $this->serializer->deserialize($raw, RolePost::className(), 'json');

        $this->entityManager->beginTransaction();
        try {
            $this->rbacManager->update($roleName, $roleObject);

            if (isset($roleObject->child) && $roleObject->child !== null) {
                $this->rbacManager->removeChildren($role);
                foreach ($roleObject->child as $child) {
                    $this->rbacManager->addChild($role, $this->rbacManager->getPermission($child['name']));
                }
            }

            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{roleName}/switch",name="switch", methods={"PATCH"})
     * @param Request $request
     * @param string $roleName
     * @return JsonResponse
     * @throws Exception
     */
    public function switchAction(Request $request, string $roleName)
    {
        $role = $this->authItemRepository->findOneBy(['name' => $roleName]);
        if (empty($role)) {
            throw new NotFoundHttpException();
        }
        $raw = $request->getContent();
        $this->entityManager->beginTransaction();
        try {
            $roleObject = $this->serializer->deserialize($raw, RoleStatusPost::className(), 'json');

            $role->setStatus($roleObject->status ?? $role->getStatus());

            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }


        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{roleName}",name="delete", methods={"DELETE"})
     * @param string $roleName
     * @return JsonResponse
     * @throws Exception
     */
    public function removeAction(string $roleName)
    {
        $role = $this->rbacManager->getRole($roleName);

        if (empty($role)) {
            throw new NotFoundHttpException();
        }
        $this->entityManager->beginTransaction();
        try {
            $this->rbacManager->remove($role);
            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->json(['message' => 'OK']);

    }

    /**
     * @Route("/{roleName}/permissions",name="add_permission", methods={"POST"})
     * @param Request $request
     * @param string $roleName
     * @return JsonResponse
     * @throws Exception
     */
    public function addPermissionAction(Request $request, string $roleName)
    {
        $role = $this->rbacManager->getRole($roleName);
        if (empty($role)) {
            throw new NotFoundHttpException();
        }

        $raw = $request->getContent();
        $children = $this->serializer->deserialize($raw, Permission::className() . '[]', 'json');
        foreach ($children as $child) {
            $can = $this->rbacManager->canAddChild($role, $child);
            if (!$can) {
                throw new AccessDeniedHttpException();
            }
        }

        $this->entityManager->beginTransaction();
        try {
            foreach ($children as $child) {
                $this->rbacManager->addChild($role, $child);
            }
            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/{roleName}/assign",name="assign", methods={"POST"})
     * @param Request $request
     * @param string $roleName
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function assignAction(Request $request, string $roleName)
    {

        $role = $this->rbacManager->getRole($roleName);
        if (empty($role)) {
            throw new NotFoundHttpException();
        }

        $users = $this->parse($request);
        if (empty($users)) {
            throw new BadRequestHttpException('User\'s ID is missing');
        }

        $this->entityManager->beginTransaction();
        try {
            foreach ($users as $user) {
                if (empty($this->rbacManager->getAssignment($role->name, $user->id))) {
                    $this->rbacManager->assign($role, $user->id);
                }
            }
            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->entityManager->rollback();
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->json(['message' => 'OK']);
    }
}