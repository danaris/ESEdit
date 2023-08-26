<?php

namespace App\Repository\Sky;

use App\Entity\Sky\OutfitEffect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutfitEffect>
 *
 * @method OutfitEffect|null find($id, $lockMode = null, $lockVersion = null)
 * @method OutfitEffect|null findOneBy(array $criteria, array $orderBy = null)
 * @method OutfitEffect[]    findAll()
 * @method OutfitEffect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutfitEffectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutfitEffect::class);
    }

    public function save(OutfitEffect $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OutfitEffect $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OutfitEffect[] Returns an array of OutfitEffect objects
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

//    public function findOneBySomeField($value): ?OutfitEffect
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
