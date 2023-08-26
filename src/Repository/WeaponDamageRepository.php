<?php

namespace App\Repository;

use App\Entity\WeaponDamage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeaponDamage>
 *
 * @method WeaponDamage|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeaponDamage|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeaponDamage[]    findAll()
 * @method WeaponDamage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeaponDamageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeaponDamage::class);
    }

//    /**
//     * @return WeaponDamage[] Returns an array of WeaponDamage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WeaponDamage
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
