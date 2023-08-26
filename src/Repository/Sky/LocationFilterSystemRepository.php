<?php

namespace App\Repository\Sky;

use App\Entity\Sky\LocationFilterSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LocationFilterSystem>
 *
 * @method LocationFilterSystem|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocationFilterSystem|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocationFilterSystem[]    findAll()
 * @method LocationFilterSystem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationFilterSystemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationFilterSystem::class);
    }

    public function save(LocationFilterSystem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LocationFilterSystem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LocationFilterSystem[] Returns an array of LocationFilterSystem objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LocationFilterSystem
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
