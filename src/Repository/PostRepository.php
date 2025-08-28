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
}
