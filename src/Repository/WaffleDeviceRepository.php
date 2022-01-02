<?php

namespace App\Repository;

use App\Entity\WaffleDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WaffleDevice|null find($id, $lockMode = null, $lockVersion = null)
 * @method WaffleDevice|null findOneBy(array $criteria, array $orderBy = null)
 * @method WaffleDevice[]    findAll()
 * @method WaffleDevice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WaffleDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaffleDevice::class);
    }

    // /**
    //  * @return WaffleDevice[] Returns an array of WaffleDevice objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WaffleDevice
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
