<?php

namespace App\Repository\Sky;

use App\Entity\Sky\OutfitSprite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutfitSprite>
 *
 * @method OutfitSprite|null find($id, $lockMode = null, $lockVersion = null)
 * @method OutfitSprite|null findOneBy(array $criteria, array $orderBy = null)
 * @method OutfitSprite[]    findAll()
 * @method OutfitSprite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutfitSpriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutfitSprite::class);
    }

    public function save(OutfitSprite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OutfitSprite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OutfitSprite[] Returns an array of OutfitSprite objects
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

//    public function findOneBySomeField($value): ?OutfitSprite
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
