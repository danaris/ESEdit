<?php

namespace App\Repository\Sky;

use App\Entity\Sky\Hardpoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hardpoint>
 *
 * @method Hardpoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hardpoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hardpoint[]    findAll()
 * @method Hardpoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HardpointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hardpoint::class);
    }

//    /**
//     * @return Hardpoint[] Returns an array of Hardpoint objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Hardpoint
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
