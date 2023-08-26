<?php

namespace App\Repository\Sky;

use App\Entity\Sky\SystemNeighbor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemNeighbor>
 *
 * @method SystemNeighbor|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemNeighbor|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemNeighbor[]    findAll()
 * @method SystemNeighbor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SystemNeighborRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemNeighbor::class);
    }

//    /**
//     * @return SystemNeighbor[] Returns an array of SystemNeighbor objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SystemNeighbor
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
