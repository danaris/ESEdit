<?php

namespace App\Repository\Sky;

use App\Entity\Sky\PhraseSentence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhraseSentence>
 *
 * @method PhraseSentence|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhraseSentence|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhraseSentence[]    findAll()
 * @method PhraseSentence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhraseSentenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhraseSentence::class);
    }

//    /**
//     * @return PhraseSentence[] Returns an array of PhraseSentence objects
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

//    public function findOneBySomeField($value): ?PhraseSentence
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
