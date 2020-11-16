<?php


namespace SymfonyRbac\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyRbac\Entity\AuthAssignment;

class AuthAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthAssignment::class);
    }

    /**
     * @param int $userId
     * @param array $option
     * @return array|int|string
     */
    public function getAssignmentsByUser(int $userId, $option = [])
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.userId =:userId')
            ->setParameter('userId', $userId);

        if ($option) {
            $tableFields = $this->getClassMetadata()->getFieldNames();
            foreach ($option as $field => $item) {
                if (in_array($field, $tableFields) && !is_null($item)) {
                    $str = "i.{$field} =:{$field}";
                    $query->andWhere($str)->setParameter($field, $item);
                }
            }
        }

        return $query->getQuery()->getResult();
    }


    /**
     * @param string $roleName
     * @param array $option
     * @return int|mixed|string
     */
    public function getAssignmentsByRole(string $roleName, $option = [])
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.itemName =:itemName')
            ->setParameter('itemName', $roleName);

        if ($option) {
            $tableFields = $this->getClassMetadata()->getFieldNames();
            foreach ($option as $field => $item) {
                if (in_array($field, $tableFields) && !is_null($item)) {
                    $str = "i.{$field} =:{$field}";
                    $query->andWhere($str)->setParameter($field, $item);
                }
            }
        }

        return $query->getQuery()->getResult();
    }
}