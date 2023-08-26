<?php

namespace App\Repository\Sky;

use App\Entity\Sky\GovernmentPenalty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GovernmentPenalty>
 *
 * @method GovernmentPenalty|null find($id, $lockMode = null, $lockVersion = null)
 * @method GovernmentPenalty|null findOneBy(array $criteria, array $orderBy = null)
 * @method GovernmentPenalty[]    findAll()
 * @method GovernmentPenalty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GovernmentPenaltyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GovernmentPenalty::class);
    }

    public function save(GovernmentPenalty $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GovernmentPenalty $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return GovernmentPenalty[] Returns an array of GovernmentPenalty objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?GovernmentPenalty
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
