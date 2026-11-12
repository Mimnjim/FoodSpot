<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }


    public function findByFilters(?string $category, ?string $search, ?string $city = null): array
    {
        // Construire une requête sur l'entité Restaurant
        $queryBuilder = $this->createQueryBuilder('r');

        // Filtrer par catégorie si une catégorie est sélectionnée
        if ($category !== null && $category !== '' && $category !== 'Toutes') {
            $queryBuilder
                ->andWhere('r.category = :category')
                ->setParameter('category', $category);
        }   

        // Rechercher dans le nom, la catégorie ou la ville
        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(r.name) LIKE LOWER(:search)
                    OR LOWER(r.category) LIKE LOWER(:search)
                    OR LOWER(r.city) LIKE LOWER(:search)'
                )
                ->setParameter('search', '%' . $search . '%');
        }

        if ($city !== null && trim($city) !== '') {
            $queryBuilder
                ->andWhere('LOWER(r.city) LIKE LOWER(:city)')
                ->setParameter('city', '%' . trim($city) . '%');
        }

        // Exécuter la requête et retourner les résultats
        return $queryBuilder->getQuery()->getResult();
    }


//    /**
//     * @return Restaurant[] Returns an array of Restaurant objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Restaurant
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
