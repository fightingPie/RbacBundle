<?php

namespace SymfonyRbac\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use SymfonyRbac\Rbac\Role;
use SymfonyRbac\Repository\AuthAssignmentRepository;
use SymfonyRbac\Services\RbacManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class AssignmentController
 * @package SymfonyRbac\Controller
 * @Route("/rbac/assignments", name="rbac_assignments_")
 */
class AssignmentController extends BaseController
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
     * @var AuthAssignmentRepository
     */
    private $authAssignmentRepository;

    /**
     * AssignmentController constructor.
     * @param RbacManager $rbacManager
     * @param AuthAssignmentRepository $authAssignmentRepository
     */
    public function __construct(RbacManager $rbacManager, AuthAssignmentRepository $authAssignmentRepository)
    {
        $this->rbacManager = $rbacManager;

        $this->serializer = new Serializer([new ObjectNormalizer(), new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);

        $this->authAssignmentRepository = $authAssignmentRepository;
    }


    /**
     * @Route("/{user}",name="user", methods={"GET"})
     * @param Request $request
     * @param int $user
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function userAction(Request $request, int $user, PaginatorInterface $paginator)
    {

        $page = $request->query->getInt('page', 1);
        $pageSize = $request->query->getInt('page_size', 10);

        $assignments = $this->authAssignmentRepository->getAssignmentsByUser($user);
        $assignmentList = $paginator->paginate($assignments, $page, $pageSize);
        if (empty($assignmentList)) {
            throw new NotFoundHttpException();
        }

        return $this->paginatedJson(
            $assignmentList->getItems(),
            $assignmentList->getCurrentPageNumber(),
            $assignmentList->getItemNumberPerPage(),
            $assignmentList->getTotalItemCount()
        );
    }


    /**
     * @Route("/user/{user}/add",name="user_add", methods={"POST"})
     * @param Request $request
     * @param int $user
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function userAddAction(Request $request, int $user)
    {
        $raw = $request->getContent();

        $roles = $this->serializer->deserialize($raw, Role::className() . '[]', 'json');

        foreach ($roles as $role) {
            if (empty($this->rbacManager->getAssignment($role->name, $user))) {
                $this->rbacManager->assign($role, $user);
            }
        }

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/user/{user}/remove",name="user_delete", methods={"DELETE"})
     * @param int $user
     * @return JsonResponse
     * @throws Exception
     */
    public function removeAction(int $user)
    {
        $assignments = $this->rbacManager->getAssignments($user);

        if (empty($assignments)) {
            throw new NotFoundHttpException();
        }

        $this->rbacManager->revokeAll($user);

        return $this->json(['message' => 'OK']);
    }


    /**
     * @Route("/user/{user}/role/{roleName}",name="user_role_delete", methods={"DELETE"})
     * @param int $user
     * @param string $roleName
     * @return JsonResponse
     */
    public function removeOneAction(int $user, string $roleName)
    {

        $assignments = $this->rbacManager->getAssignment($roleName, $user);

        $role = $this->rbacManager->getRole($roleName);

        if (empty($assignments) || empty($role)) {
            throw new NotFoundHttpException();
        }

        $this->rbacManager->revoke($role, $user);

        return $this->json(['message' => 'OK']);
    }


}