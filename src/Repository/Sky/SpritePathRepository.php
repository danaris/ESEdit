<?php

namespace App\Repository\Sky;

use App\Entity\Sky\SpritePath;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpritePath>
 *
 * @method SpritePath|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpritePath|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpritePath[]    findAll()
 * @method SpritePath[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpritePathRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpritePath::class);
    }

//    /**
//     * @return SpritePath[] Returns an array of SpritePath objects
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

//    public function findOneBySomeField($value): ?SpritePath
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
