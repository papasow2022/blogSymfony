<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
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

    public function search(?string $query = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('p');
        
        if ($query) {
            $qb->andWhere('p.title LIKE :query OR p.content LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }
        
        return $qb->orderBy('p.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Récupère le nombre total de vues de tous les articles
     */
    public function getTotalViews(): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.viewsCount)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Récupère le nombre total de likes de tous les articles
     */
    public function getTotalLikes(): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.likesCount)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Récupère les articles les plus populaires
     */
    public function findMostPopular(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.viewsCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les articles les plus likés
     */
    public function findMostLiked(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.likesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
