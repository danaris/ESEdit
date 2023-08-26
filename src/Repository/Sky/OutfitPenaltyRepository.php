<?php

namespace App\Repository\Sky;

use App\Entity\Sky\OutfitPenalty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutfitPenalty>
 *
 * @method OutfitPenalty|null find($id, $lockMode = null, $lockVersion = null)
 * @method OutfitPenalty|null findOneBy(array $criteria, array $orderBy = null)
 * @method OutfitPenalty[]    findAll()
 * @method OutfitPenalty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutfitPenaltyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutfitPenalty::class);
    }

    public function save(OutfitPenalty $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OutfitPenalty $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OutfitPenalty[] Returns an array of OutfitPenalty objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OutfitPenalty
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
