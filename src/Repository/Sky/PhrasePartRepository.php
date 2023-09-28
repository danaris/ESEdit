<?php

namespace App\Repository\Sky;

use App\Entity\Sky\PhrasePart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhrasePart>
 *
 * @method PhrasePart|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhrasePart|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhrasePart[]    findAll()
 * @method PhrasePart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhrasePartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhrasePart::class);
    }

//    /**
//     * @return PhrasePart[] Returns an array of PhrasePart objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PhrasePart
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
