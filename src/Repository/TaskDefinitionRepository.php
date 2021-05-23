<?php

namespace App\Repository;

use App\Entity\TaskDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TaskDefinition|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskDefinition|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskDefinition[]    findAll()
 * @method TaskDefinition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskDefinition::class);
    }

    // /**
    //  * @return TaskDefinition[] Returns an array of TaskDefinition objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TaskDefinition
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
