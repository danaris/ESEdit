<?php

namespace App\Repository\Sky;

use App\Entity\Sky\ShipOutfit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShipOutfit>
 *
 * @method ShipOutfit|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShipOutfit|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShipOutfit[]    findAll()
 * @method ShipOutfit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShipOutfitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShipOutfit::class);
    }

//    /**
//     * @return ShipOutfit[] Returns an array of ShipOutfit objects
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

//    public function findOneBySomeField($value): ?ShipOutfit
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
