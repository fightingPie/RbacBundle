<?php


namespace SymfonyRbac\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyRbac\Entity\AuthItem;

class AuthItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthItem::class);
    }

    /**
     * @param int $type
     * @param array $option
     * @return array|int|string
     */
    public function getItems(int $type, $option = [])
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.type =:type')
            ->setParameter('type', $type);

        if ($option) {
            $tableFields = $this->getClassMetadata()->getFieldNames();
            foreach ($option as  $item) {
                $field = $item['key'] ?? null;
                $value = $item['value'] ?? null;
                if (in_array($field, $tableFields) && !is_null($value)) {
                    if (is_array($value)){
                        $str = "i.{$field} IN (:{$field})";
                        $query->andWhere($str)->setParameter($field, implode(',',$value));
                    }else{
                        $str = "i.{$field} =:{$field}";
                        $query->andWhere($str)->setParameter($field, $value);
                    }
                }
            }
        }

        return $query->getQuery();
    }
}