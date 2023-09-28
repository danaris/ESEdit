<?php

namespace App\Repository\Sky;

use App\Entity\Sky\PhraseChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhraseChoice>
 *
 * @method PhraseChoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhraseChoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhraseChoice[]    findAll()
 * @method PhraseChoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhraseChoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhraseChoice::class);
    }

//    /**
//     * @return PhraseChoice[] Returns an array of PhraseChoice objects
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

//    public function findOneBySomeField($value): ?PhraseChoice
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
