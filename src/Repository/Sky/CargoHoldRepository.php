<?php

namespace App\Repository\Sky;

use App\Entity\Sky\CargoHold;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CargoHold>
 *
 * @method CargoHold|null find($id, $lockMode = null, $lockVersion = null)
 * @method CargoHold|null findOneBy(array $criteria, array $orderBy = null)
 * @method CargoHold[]    findAll()
 * @method CargoHold[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CargoHoldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CargoHold::class);
    }

//    /**
//     * @return CargoHold[] Returns an array of CargoHold objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CargoHold
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
